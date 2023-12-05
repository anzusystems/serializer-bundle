<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Handler\Handlers;

use AnzuSystems\SerializerBundle\Exception\DeserializationException;
use AnzuSystems\SerializerBundle\Exception\SerializerException;
use AnzuSystems\SerializerBundle\Metadata\Metadata;
use BackedEnum;
use Symfony\Component\PropertyInfo\Type;
use UnitEnum;

final class EnumHandler extends AbstractHandler
{
    public static function supportsSerialize(mixed $value): bool
    {
        return $value instanceof UnitEnum;
    }

    /**
     * @param UnitEnum $value
     */
    public function serialize(mixed $value, Metadata $metadata): int|string
    {
        if ($value instanceof BackedEnum) {
            return $value->value;
        }

        return $value->name;
    }

    public static function supportsDeserialize(mixed $value, string $type): bool
    {
        return is_a($type, UnitEnum::class, true);
    }

    public function deserialize(mixed $value, Metadata $metadata): UnitEnum
    {
        if (is_a($metadata->type, BackedEnum::class, true)) {
            $enumValue = $metadata->type::tryFrom($value);
            if ($enumValue instanceof BackedEnum) {
                return $enumValue;
            }

            throw new DeserializationException(sprintf(
                'Cannot deserialize value "%s" into a BackedEnum. Possible options are: "%s".',
                $value,
                implode('", "', array_column($metadata->type::cases(), 'value'))
            ));
        }
        if (is_a($metadata->type, UnitEnum::class, true)) {
            foreach ($metadata->type::cases() as $case) {
                if ($value === $case->name) {
                    return $case;
                }
            }

            throw new DeserializationException(sprintf(
                'Cannot deserialize value "%s" into a UnitEnum. Possible options are: "%s".',
                $value,
                implode('", "', array_column($metadata->type::cases(), 'name'))
            ));
        }

        throw new SerializerException(sprintf('Unsupported value for %s::%s', self::class, __METHOD__));
    }

    public static function supportsDescribe(string $property, Metadata $metadata): bool
    {
        return is_a($metadata->type, UnitEnum::class, true);
    }

    public function describe(string $property, Metadata $metadata): array
    {
        $description = parent::describe($property, $metadata);

        /** @var UnitEnum $enumClass */
        $enumClass = $metadata->type;
        $enums = [];
        foreach ($enumClass::cases() as $enumCase) {
            $enums[] = $enumCase->value;
        }
        $description['enum'] = $enums;
        $description['type'] = Type::BUILTIN_TYPE_STRING;

        return $description;
    }
}
