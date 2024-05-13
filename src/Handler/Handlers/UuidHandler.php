<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Handler\Handlers;

use AnzuSystems\SerializerBundle\Context\SerializationContext;
use AnzuSystems\SerializerBundle\Metadata\Metadata;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Uid\AbstractUid;
use Symfony\Component\Uid\MaxUuid;
use Symfony\Component\Uid\NilUuid;
use Symfony\Component\Uid\Uuid;

final class UuidHandler extends AbstractHandler
{
    public static function supportsSerialize(mixed $value): bool
    {
        return $value instanceof Uuid;
    }

    /**
     * @param Uuid $value
     */
    public function serialize(mixed $value, Metadata $metadata, SerializationContext $context): string
    {
        return $value->toRfc4122();
    }

    public static function supportsDeserialize(mixed $value, string $type): bool
    {
        return is_a($type, Uuid::class, true);
    }

    public function deserialize(mixed $value, Metadata $metadata): ?AbstractUid
    {
        if (null === $value) {
            return null;
        }
        if ('00000000-0000-0000-0000-000000000000' === $value || '' === $value) {
            return new NilUuid();
        }
        if ('ffffffff-ffff-ffff-ffff-ffffffffffff' === $value) {
            return new MaxUuid();
        }

        return Uuid::fromString($value);
    }

    public static function supportsDescribe(string $property, Metadata $metadata): bool
    {
        return is_a($metadata->type, Uuid::class, true);
    }

    public function describe(string $property, Metadata $metadata): array
    {
        $description = parent::describe($property, $metadata);
        $description['type'] = Type::BUILTIN_TYPE_STRING;
        $description['title'] = 'UUID';

        return $description;
    }
}
