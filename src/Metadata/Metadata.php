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
     * @param list<string> $scalarUnionTypes the types kept as they are in the input, for a union of scalars and array
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
        public bool $filledByConstructor = false,
        public bool $constructorReadonly = false,
        public bool $readInClassScope = false,
        public bool $writeInClassScope = false,
        public array $scalarUnionTypes = [],
    ) {
    }
}
