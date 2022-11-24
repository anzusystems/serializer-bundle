<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle;

use AnzuSystems\SerializerBundle\Exception\SerializerException;
use AnzuSystems\SerializerBundle\Service\JsonDeserializer;
use AnzuSystems\SerializerBundle\Service\JsonSerializer;

final class Serializer
{
    public function __construct(
        private readonly JsonSerializer $jsonSerializer,
        private readonly JsonDeserializer $jsonDeserializer,
    ) {
    }

    /**
     * @template T
     *
     * @param class-string<T> $className
     *
     * @return T
     *
     * @throws SerializerException
     *
     * @psalm-suppress InvalidReturnType
     * @psalm-suppress InvalidReturnStatement
     */
    public function deserialize(string $data, string $className): object
    {
        return $this->jsonDeserializer->deserialize($data, $className);
    }

    /**
     * @template T
     *
     * @param class-string<T> $className
     *
     * @return iterable<string|int, T>
     *
     * @throws SerializerException
     */
    public function deserializeIterable(string $data, string $className, iterable $iterable): iterable
    {
        return $this->jsonDeserializer->deserialize($data, $className, $iterable);
    }

    /**
     * @template T
     *
     * @param class-string<T> $className
     *
     * @return T|iterable<string|int, T>
     *
     * @throws SerializerException
     */
    public function fromArray(array $data, string $className, ?iterable $iterable = null): object|iterable
    {
        return $this->jsonDeserializer->fromArray($data, $className, $iterable);
    }

    /**
     * @template T
     *
     * @param class-string<T> $className
     *
     * @return iterable<string|int, T>
     *
     * @throws SerializerException
     */
    public function fromArrayToIterable(array $data, string $className, ?iterable $iterable = null): iterable
    {
        return $this->jsonDeserializer->fromArray($data, $className, $iterable);
    }

    /**
     * @throws SerializerException
     */
    public function serialize(object|iterable $data): string
    {
        return $this->jsonSerializer->serialize($data);
    }

    /**
     * @throws SerializerException
     */
    public function toArray(object|iterable $data): array
    {
        return $this->jsonSerializer->toArray($data);
    }
}
