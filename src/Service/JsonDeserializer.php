<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Service;

use AnzuSystems\SerializerBundle\Exception\DeserializationException;
use AnzuSystems\SerializerBundle\Exception\SerializerException;
use AnzuSystems\SerializerBundle\Handler\HandlerResolver;
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
        $object = new $className();
        foreach ($objectMetadata as $name => $metadata) {
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
            } catch (Throwable) {
                throw new SerializerException('Unable to deserialize "' . $name . '". Check type.');
            }
        }

        return $object;
    }
}
