<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Handler\Handlers;

use AnzuSystems\SerializerBundle\Helper\SerializerHelper;
use AnzuSystems\SerializerBundle\Metadata\Metadata;
use Symfony\Component\PropertyInfo\Type;

abstract class AbstractHandler implements HandlerInterface
{
    public static function supportsSerialize(mixed $value): bool
    {
        return false;
    }

    public static function supportsDeserialize(mixed $value, string $type): bool
    {
        return false;
    }

    public static function getPriority(): int
    {
        return 0;
    }

    public static function supportsDescribe(string $property, Metadata $metadata): bool
    {
        return false;
    }

    public function describe(string $property, Metadata $metadata): array
    {
        $type = SerializerHelper::getOaFriendlyType($metadata->type);
        $description = [
            'property' => $property,
            'type' => $type,
        ];
        if (Type::BUILTIN_TYPE_FLOAT === $metadata->type) {
            $description['format'] = 'float';
        }
        if (null === $metadata->setter) {
            $description['readOnly'] = true;
        }
        if ($metadata->isNullable) {
            $description['nullable'] = true;
        }
        if (Type::BUILTIN_TYPE_ARRAY === $metadata->type) {
            $itemType = [];
            if (null !== $metadata->customType && '' !== $metadata->customType) {
                $itemType = ['type' => $metadata->customType];
            }
            $description['items'] = $itemType;
        }

        return $description;
    }
}
