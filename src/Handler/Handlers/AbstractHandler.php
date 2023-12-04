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
        $description = [
            'property' => $property,
            'type' => SerializerHelper::getOaFriendlyType($metadata->type),
        ];
        if (null === $metadata->setter) {
            $description['readOnly'] = true;
        }
        if ($metadata->isNullable) {
            $description['nullable'] = true;
        }
        if (Type::BUILTIN_TYPE_ARRAY === $metadata->type) {
            $itemType = [];
            if ($metadata->customType) {
                $itemType = ['type' => $metadata->customType];
            }
            $description['items'] = $itemType;
        }

        return $description;
    }
}
