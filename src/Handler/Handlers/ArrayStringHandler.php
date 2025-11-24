<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Handler\Handlers;

use AnzuSystems\SerializerBundle\Context\SerializationContext;
use AnzuSystems\SerializerBundle\Exception\SerializerException;
use AnzuSystems\SerializerBundle\Metadata\Metadata;
use Symfony\Component\TypeInfo\TypeIdentifier;

final class ArrayStringHandler extends AbstractHandler
{
    public function serialize(mixed $value, Metadata $metadata, SerializationContext $context): string
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

    public static function supportsDescribe(string $property, Metadata $metadata): bool
    {
        return is_a($metadata->customHandler, self::class, true);
    }

    public function describe(string $property, Metadata $metadata): array
    {
        $description = parent::describe($property, $metadata);
        $description['type'] = TypeIdentifier::STRING->value;
        $description['format'] = 'string, values separated by comma';
        unset($description['items']);

        return $description;
    }

    private function getDeserializeFunction(Metadata $metadata): \Closure
    {
        return match ($metadata->customType) {
            TypeIdentifier::INT->value => fn (string $item): int => (int) $item,
            TypeIdentifier::FLOAT->value => fn (string $item): float => (float) $item,
            default => fn (string $item): string => trim($item),
        };
    }
}
