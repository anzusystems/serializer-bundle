<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Metadata;

final class Metadata
{
    /**
     * @param class-string|string $type
     * @param class-string|string|null $customType
     * @param array<string, class-string>|null $discriminatorMap
     * @param null|array<string, 'ASC'|'DESC'> $orderBy
     * @param class-string|null $declaringClass
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
        public ?array $orderBy = null,
        public bool $getterIsMethod = true,
        public bool $setterIsMethod = true,
        public ?string $declaringClass = null,
        public bool $readable = true,
        public bool $promoted = false,
        public bool $constructorReadonly = false,
    ) {
    }
}
