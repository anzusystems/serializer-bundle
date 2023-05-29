<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Metadata;

final class Metadata
{
    /**
     * @param class-string|string $type
     * @param class-string|string|null $customType
     * @param array<string, class-string>|null $discriminatorMap
     */
    public function __construct(
        public string $type,
        public bool $isNullable,
        public string $getter,
        public ?string $property = null,
        public ?string $setter = null,
        public ?string $customHandler = null,
        public ?string $customType = null,
        public ?string $strategy = null,
        public ?string $persistedName = null,
        public ?array $discriminatorMap = null,
    ) {
    }
}
