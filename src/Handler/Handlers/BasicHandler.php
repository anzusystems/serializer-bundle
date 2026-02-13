<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Handler\Handlers;

use AnzuSystems\SerializerBundle\Context\SerializationContext;
use AnzuSystems\SerializerBundle\Metadata\Metadata;
use Symfony\Component\TypeInfo\TypeIdentifier;

final class BasicHandler extends AbstractHandler
{
    public const array BASIC_TYPES = [
        TypeIdentifier::INT->value,
        TypeIdentifier::STRING->value,
        TypeIdentifier::FLOAT->value,
        TypeIdentifier::BOOL->value,
    ];

    public static function getPriority(): int
    {
        return 10;
    }

    public static function supportsSerialize(mixed $value): bool
    {
        return is_scalar($value) || null === $value;
    }

    public function serialize(mixed $value, Metadata $metadata, SerializationContext $context): string|int|bool|null|float
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
            TypeIdentifier::STRING->value => (string) $value,
            TypeIdentifier::INT->value => (int) $value,
            TypeIdentifier::FLOAT->value => (float) $value,
            TypeIdentifier::BOOL->value => filter_var($value, FILTER_VALIDATE_BOOL)
        };
    }

    public static function supportsDescribe(string $property, Metadata $metadata): bool
    {
        return in_array($metadata->type, self::BASIC_TYPES, true);
    }
}
