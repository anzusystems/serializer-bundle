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
            $dataValue = $data[$name];
            $value = $this->handlerResolver
                ->getDeserializationHandler($dataValue, $metadata->type, $metadata->customHandler)
                ->deserialize($dataValue, $metadata)
            ;
            if (null === $value && false === $metadata->isNullable) {
                continue;
            }

            try {
                $metadata->getterSetterStrategy ? $object->{$metadata->setter}($value) : $object->{$metadata->property} = $value;
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
            $handlerClass = $metadata->customHandler;
            if (null === $handlerClass || false === is_a($handlerClass, BatchDeserializeHandlerInterface::class, true)) {
                continue;
            }

            $batches[$handlerClass][] = new BatchItem($data[$name], $metadata);
        }

        return $batches;
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
        if (empty($constructorMetadata)) {
            // initialize object without parameters
            return new $className();
        }

        // initialize object with parameters
        $params = [];
        foreach ($constructorMetadata as $name => $metadata) {
            /** @var Metadata $metadata */
            if (false === isset($data[$name], $propMetadata[$name])) {
                throw new SerializerException(
                    sprintf(
                        'Unable to deserialize "%s". Required constructor property "%s" missing in data or serializable properties.',
                        $className,
                        $name
                    )
                );
            }

            $dataValue = $data[$name];
            $params[] = $this->handlerResolver
                ->getDeserializationHandler($dataValue, $metadata->type, $metadata->customHandler)
                ->deserialize($dataValue, $metadata);
        }

        return new $className(...$params);
    }
}
