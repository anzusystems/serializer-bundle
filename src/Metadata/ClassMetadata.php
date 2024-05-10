<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Metadata;

use AnzuSystems\SerializerBundle\Exception\SerializerException;

final readonly class ClassMetadata
{
    /**
     * @param array<string, Metadata> $metadata
     * @param array<string, Metadata> $constructorMetadata
     */
    public function __construct(
        private array $metadata,
        private array $constructorMetadata
    ) {
    }

    public function has(string $name): bool
    {
        return isset($this->metadata[$name]);
    }

    /**
     * @throws SerializerException
     */
    public function get(string $name): Metadata
    {
        if (!isset($this->metadata[$name])) {
            throw new SerializerException(sprintf('Metadata "%s" does not exist.', $name));
        }

        return $this->metadata[$name];
    }

    /**
     * @return array<string, Metadata>
     */
    public function getAll(): array
    {
        return $this->metadata;
    }

    /**
     * @return array<string, Metadata>
     */
    public function getConstructorMetadata(): array
    {
        return $this->constructorMetadata;
    }
}
