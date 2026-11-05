<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Handler\Handlers;

use AnzuSystems\SerializerBundle\Context\SerializationContext;
use AnzuSystems\SerializerBundle\Exception\DeserializationException;
use AnzuSystems\SerializerBundle\Metadata\Metadata;
use Stringable;
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

    /**
     * @throws DeserializationException
     */
    public function deserialize(mixed $value, Metadata $metadata): string|int|bool|null|float
    {
        if ($metadata->isNullable && null === $value) {
            return null;
        }

        return match ($metadata->type) {
            TypeIdentifier::STRING->value => is_string($value) ? $value : $this->toString($value, $metadata),
            TypeIdentifier::INT->value => is_int($value) ? $value : (int) $this->assertNumberLike($value, $metadata),
            TypeIdentifier::FLOAT->value => is_float($value) ? $value : (float) $this->assertNumberLike($value, $metadata),
            TypeIdentifier::BOOL->value => filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE)
                ?? throw $this->invalidValue($value, $metadata),
            default => throw $this->invalidValue($value, $metadata),
        };
    }

    public static function supportsDescribe(string $property, Metadata $metadata): bool
    {
        return in_array($metadata->type, self::BASIC_TYPES, true);
    }

    /**
     * @throws DeserializationException
     */
    private function toString(mixed $value, Metadata $metadata): string
    {
        return is_scalar($value) || null === $value || $value instanceof Stringable
            ? (string) $value
            : throw $this->invalidValue($value, $metadata);
    }

    /**
     * Stored data and other systems send an empty string or null for a missing number, so those stay valid.
     *
     * @throws DeserializationException
     */
    private function assertNumberLike(mixed $value, Metadata $metadata): mixed
    {
        return is_numeric($value) || null === $value || '' === $value || is_bool($value)
            ? $value
            : throw $this->invalidValue($value, $metadata);
    }
}
