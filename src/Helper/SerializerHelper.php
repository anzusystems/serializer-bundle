<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Helper;

use Symfony\Component\PropertyInfo\Type;

final class SerializerHelper
{
    public static function getOaFriendlyType(string $type): string
    {
        return match ($type) {
            Type::BUILTIN_TYPE_INT => 'integer',
            Type::BUILTIN_TYPE_BOOL => 'boolean',
            Type::BUILTIN_TYPE_FLOAT => 'number',
            default => $type,
        };
    }

    public static function getClassBaseName(string $className): string
    {
        return substr((string) strrchr($className, '\\'), 1);
    }
}
