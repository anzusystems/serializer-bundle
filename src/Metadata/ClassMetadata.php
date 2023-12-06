<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Metadata;

use AnzuSystems\SerializerBundle\Exception\SerializerException;

final class ClassMetadata
{
    /**
     * @param array<string, Metadata> $metadata
     */
    public function __construct(
        private array $metadata
    ) {
    }

    public function has(string $name): bool
    {
        return isset($this->metadata[$name]);
    }

    public function get(string $name): Metadata
    {
        if (!isset($this->metadata[$name])) {
            throw new SerializerException(sprintf('Metadata "%s" does not exist.', $name));
        }

        return $this->metadata[$name];
    }

    public function set(string $name, Metadata $metadata): self
    {
        $this->metadata[$name] = $metadata;

        return $this;
    }

    /**
     * @return array<string, Metadata>
     */
    public function getAll(): array
    {
        return $this->metadata;
    }
}
