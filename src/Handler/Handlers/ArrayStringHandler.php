<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Handler\Handlers;

use AnzuSystems\SerializerBundle\Exception\SerializerException;
use AnzuSystems\SerializerBundle\Metadata\Metadata;
use Symfony\Component\PropertyInfo\Type;

final class ArrayStringHandler extends AbstractHandler
{
    public function serialize(mixed $value, Metadata $metadata): string
    {
        if (is_array($value)) {
            return implode(',', $value);
        }

        throw new SerializerException('Unsupported value for ' . self::class . '::' . __FUNCTION__);
    }

    public function deserialize(mixed $value, Metadata $metadata): array
    {
        if (empty($value)) {
            return [];
        }
        if (is_string($value)) {
            return array_map(
                $this->getDeserializeFunction($metadata),
                explode(',', $value)
            );
        }

        throw new SerializerException('Unsupported value for ' . self::class . '::' . __FUNCTION__);
    }

    private function getDeserializeFunction(Metadata $metadata): \Closure
    {
        return match ($metadata->type) {
            Type::BUILTIN_TYPE_INT => fn (string $item): int => (int) $item,
            Type::BUILTIN_TYPE_FLOAT => fn (string $item): float => (float) $item,
            default => fn (string $item): string => trim($item),
        };
    }
}
