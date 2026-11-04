<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Service;

use AnzuSystems\SerializerBundle\Exception\DeserializationException;
use AnzuSystems\SerializerBundle\Exception\SerializerException;
use AnzuSystems\SerializerBundle\Handler\BatchItem;
use AnzuSystems\SerializerBundle\Handler\HandlerResolver;
use AnzuSystems\SerializerBundle\Handler\Handlers\BatchDeserializeHandlerInterface;
use AnzuSystems\SerializerBundle\Metadata\ClassMetadata;
use AnzuSystems\SerializerBundle\Metadata\Metadata;
use AnzuSystems\SerializerBundle\Metadata\MetadataRegistry;
use Doctrine\Common\Collections\Collection;
use JsonException;
use Throwable;

final class JsonDeserializer
{
    public function __construct(
        private readonly HandlerResolver $handlerResolver,
        private readonly MetadataRegistry $metadataRegistry,
        private readonly PropertyAccessor $propertyAccessor = new PropertyAccessor(),
    ) {
    }

    /**
     * @param class-string $className
     *
     * @throws SerializerException
     */
    public function deserialize(string $data, string $className, ?iterable $iterable = null): object|iterable
    {
        try {
            $dataArray = json_decode($data, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $jsonException) {
            throw new DeserializationException(
                'Cannot decode JSON string provided.',
                previous: $jsonException
            );
        }

        return $this->fromArray($dataArray, $className, $iterable);
    }

    /**
     * @param class-string $className
     *
     * @throws SerializerException
     */
    public function fromArray(array $data, string $className, ?iterable $iterable = null): object|iterable
    {
        if (is_iterable($iterable)) {
            $this->prepareListBatches($data, $className);
            if ($iterable instanceof Collection) {
                foreach ($data as $key => $item) {
                    $iterable->set($key, $this->fromArray($item, $className));
                }

                return $iterable;
            }
            if (is_array($iterable)) {
                foreach ($data as $key => $item) {
                    $iterable[$key] = $this->fromArray($item, $className);
                }

                return $iterable;
            }

            throw new SerializerException('Unsupported iterable for ' . self::class . '::' . __FUNCTION__);
        }

        return $this->arrayToObject($data, $className);
    }

    /**
     * @param class-string $className
     *
     * @throws SerializerException
     */
    private function arrayToObject(array $data, string $className): object
    {
        $objectMetadata = $this->metadataRegistry->get($className);
        $this->drainBatches($this->collectBatches($objectMetadata, $data));
        $object = $this->createObjectInstance($objectMetadata, $className, $data);
        foreach ($objectMetadata->getAll() as $name => $metadata) {
            if (null === $metadata->setter || false === array_key_exists($name, $data)) {
                continue;
            }
            if ($metadata->constructorReadonly) {
                if ($this->propertyAccessor->isInitialized($object, $metadata)) {
                    // PHP refuses to write a readonly property twice, so the constructor's value wins and the input is ignored
                    continue;
                }
                if ($this->usesBatchHandler($metadata)) {
                    $this->drainBatches([
                        (string) $metadata->customHandler => [new BatchItem($data[$name], $metadata)],
                    ]);
                }
            }
            $dataValue = $data[$name];
            if (null === $dataValue && false === $metadata->isNullable && $objectMetadata->isOptionalConstructorArgument($name)) {
                // the constructor took the default value instead, and a handler would turn null into 0 or ''
                continue;
            }
            $value = $this->handlerResolver
                ->getDeserializationHandler($dataValue, $metadata->type, $metadata->customHandler)
                ->deserialize($dataValue, $metadata)
            ;
            if (null === $value && false === $metadata->isNullable) {
                continue;
            }

            try {
                $this->propertyAccessor->setValue($object, $metadata, $value);
            } catch (Throwable $exception) {
                throw new SerializerException('Unable to deserialize "' . $name . '". Check type.', 0, $exception);
            }
        }

        return $object;
    }

    /**
     * @param class-string $className
     *
     * @throws SerializerException
     */
    private function prepareListBatches(array $data, string $className): void
    {
        $objectMetadata = $this->metadataRegistry->get($className);

        $batches = [];
        foreach ($data as $item) {
            if (false === is_array($item)) {
                continue;
            }
            foreach ($this->collectBatches($objectMetadata, $item) as $handlerClass => $items) {
                $batches[$handlerClass] = [...$batches[$handlerClass] ?? [], ...$items];
            }
        }

        $this->drainBatches($batches);
    }

    /**
     * @return array<class-string, list<BatchItem>>
     */
    private function collectBatches(ClassMetadata $objectMetadata, array $data): array
    {
        $constructorMetadata = $objectMetadata->getConstructorMetadata();

        $batches = [];
        foreach ($objectMetadata->getAll() as $name => $metadata) {
            if (false === array_key_exists($name, $data)) {
                continue;
            }
            if (null === $metadata->setter && false === isset($constructorMetadata[$name])) {
                continue;
            }
            // the null check spares a method call per property, which costs 3 to 5 percent of a deserialization
            if (null === $metadata->customHandler || false === $this->usesBatchHandler($metadata)) {
                continue;
            }
            if ($metadata->constructorReadonly && false === isset($constructorMetadata[$name])) {
                // only arrayToObject() can tell, after the constructor, whether this property takes the input
                continue;
            }

            $batches[(string) $metadata->customHandler][] = new BatchItem($data[$name], $metadata);
        }

        return $batches;
    }

    private function usesBatchHandler(Metadata $metadata): bool
    {
        return null !== $metadata->customHandler
            && is_a($metadata->customHandler, BatchDeserializeHandlerInterface::class, true);
    }

    /**
     * @param array<class-string, list<BatchItem>> $batches
     *
     * @throws SerializerException
     */
    private function drainBatches(array $batches): void
    {
        foreach ($batches as $handlerClass => $items) {
            $handler = $this->handlerResolver->getHandler($handlerClass);
            if ($handler instanceof BatchDeserializeHandlerInterface) {
                $handler->prepareDeserializeBatch(...$items);
            }
        }
    }

    /**
     * @param class-string $className
     *
     * @throws SerializerException
     */
    private function createObjectInstance(ClassMetadata $objectMetadata, string $className, array $data): object
    {
        $propMetadata = $objectMetadata->getAll();
        $constructorMetadata = $objectMetadata->getConstructorMetadata();

        $arguments = [];
        foreach ($constructorMetadata as $name => $metadata) {
            $isProvided = isset($propMetadata[$name])
                && array_key_exists($name, $data)
                && (null !== $data[$name] || $metadata->isNullable);
            if ($isProvided) {
                $dataValue = $data[$name];
                $value = $this->handlerResolver
                    ->getDeserializationHandler($dataValue, $metadata->type, $metadata->customHandler)
                    ->deserialize($dataValue, $metadata);
                if (null !== $value || $metadata->isNullable) {
                    $arguments[(string) $metadata->property] = $value;

                    continue;
                }
            }

            if ($objectMetadata->isOptionalConstructorArgument($name)) {
                continue;
            }

            throw new SerializerException(
                sprintf(
                    'Unable to deserialize "%s". Required constructor property "%s" missing in data or serializable properties.',
                    $className,
                    $name
                )
            );
        }

        return new $className(...$arguments);
    }
}
