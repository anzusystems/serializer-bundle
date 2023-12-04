<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Handler\Handlers;

use AnzuSystems\SerializerBundle\Metadata\Metadata;
use Symfony\Component\PropertyInfo\Type;

final class BasicHandler extends AbstractHandler
{
    public const BASIC_TYPES = [
        Type::BUILTIN_TYPE_INT,
        Type::BUILTIN_TYPE_STRING,
        Type::BUILTIN_TYPE_FLOAT,
        Type::BUILTIN_TYPE_BOOL,
    ];

    public static function getPriority(): int
    {
        return 10;
    }

    public static function supportsSerialize(mixed $value): bool
    {
        return is_scalar($value) || null === $value;
    }

    public function serialize(mixed $value, Metadata $metadata): string|int|bool|null|float
    {
        return $value;
    }

    public static function supportsDeserialize(mixed $value, string $type): bool
    {
        return null === $value || in_array($type, self::BASIC_TYPES, true);
    }

    public function deserialize(mixed $value, Metadata $metadata): string|int|bool|null|float
    {
        if ($metadata->isNullable && null === $value) {
            return null;
        }

        return match ($metadata->type) {
            Type::BUILTIN_TYPE_STRING => (string) $value,
            Type::BUILTIN_TYPE_INT => (int) $value,
            Type::BUILTIN_TYPE_FLOAT => (float) $value,
            Type::BUILTIN_TYPE_BOOL => filter_var($value, FILTER_VALIDATE_BOOL)
        };
    }

    public static function supportsDescribe(string $property, Metadata $metadata): bool
    {
        return in_array($metadata->type, self::BASIC_TYPES, true);
    }
}
