<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Service;

use AnzuSystems\SerializerBundle\Exception\DeserializationException;
use AnzuSystems\SerializerBundle\Exception\SerializerException;
use AnzuSystems\SerializerBundle\Handler\HandlerResolver;
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
                $object->{$metadata->setter}($value);
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
            if (isset($data[$name]) && isset($propMetadata[$name])) {
                /** @var Metadata $propMeta */
                $propMeta = $propMetadata[$name];
                if ($propMeta->type !== $metadata->type) {
                    throw new SerializerException(
                        sprintf(
                            'Unable to deserialize "%s", required constructor property "%s" cannot be resolved due to different types in property "%s" and in constructor "%s".',
                            $className,
                            $name,
                            $propMeta->type,
                            $metadata->type,
                        )
                    );
                }

                $dataValue = $data[$name];
                $value = $this->handlerResolver
                    ->getDeserializationHandler($dataValue, $propMeta->type, $propMeta->customHandler)
                    ->deserialize($dataValue, $propMeta);

                $params[] = $value;

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

        return new $className(...$params);
    }
}
