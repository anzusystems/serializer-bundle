<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Helper;

use Symfony\Component\TypeInfo\TypeIdentifier;

final class SerializerHelper
{
    public static function getOaFriendlyType(string $type): string
    {
        return match ($type) {
            TypeIdentifier::INT->value => 'integer',
            TypeIdentifier::BOOL->value => 'boolean',
            TypeIdentifier::FLOAT->value => 'number',
            default => $type,
        };
    }

    public static function getClassBaseName(string $className): string
    {
        return substr((string) strrchr($className, '\\'), 1);
    }
}
