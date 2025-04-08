<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Handler\Handlers;

use AnzuSystems\SerializerBundle\Attributes\Serialize;
use AnzuSystems\SerializerBundle\Context\SerializationContext;
use AnzuSystems\SerializerBundle\Helper\SerializerHelper;
use AnzuSystems\SerializerBundle\Metadata\Metadata;
use AnzuSystems\SerializerBundle\OpenApi\SerializerModelDescriber;
use AnzuSystems\SerializerBundle\Service\JsonDeserializer;
use AnzuSystems\SerializerBundle\Service\JsonSerializer;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Selectable;
use Symfony\Component\PropertyInfo\Type;

final class ObjectHandler extends AbstractHandler
{
    public function __construct(
        private readonly JsonSerializer $jsonSerializer,
        private readonly JsonDeserializer $jsonDeserializer,
    ) {
    }

    public static function getPriority(): int
    {
        return -1;
    }

    public static function supportsSerialize(mixed $value): bool
    {
        return is_object($value) || is_array($value);
    }

    /**
     * @inheritDoc
     *
     * @param object|array $value
     */
    public function serialize(mixed $value, Metadata $metadata, SerializationContext $context): array|object
    {
        if (null !== $metadata->orderBy && $value instanceof Selectable) {
            $criteria = Criteria::create()
                ->orderBy($metadata->orderBy);

            return $this->jsonSerializer->toArray($value->matching($criteria), $metadata, $context);
        }

        return $this->jsonSerializer->toArray($value, $metadata, $context);
    }

    public static function supportsDeserialize(mixed $value, string $type): bool
    {
        return is_array($value) || ($value instanceof \stdClass && $type === Type::BUILTIN_TYPE_ARRAY);
    }

    /**
     * @inheritDoc
     *
     * @param array $value
     */
    public function deserialize(mixed $value, Metadata $metadata): object|iterable
    {
        if ($value instanceof \stdClass && Type::BUILTIN_TYPE_ARRAY === $metadata->type) {
            return (array) $value;
        }
        if (is_a($metadata->type, Collection::class, true)) {
            /** @var Collection<int|string, iterable|object> $collection */
            $collection = new ArrayCollection();
            foreach ($value as $key => $item) {
                if (is_array($item)) {
                    $collection->set($key, $this->jsonDeserializer->fromArray($item, $this->getDeserializeCustomType($item, $metadata) ?? $metadata->type));

                    continue;
                }
                $collection->set($key, $item);
            }

            return $collection;
        }
        if (Type::BUILTIN_TYPE_ARRAY === $metadata->type) {
            if ($metadata->customType || $metadata->discriminatorMap) {
                $array = [];
                foreach ($value as $key => $item) {
                    /** @psalm-suppress ArgumentTypeCoercion */
                    $array[$key] = $this->jsonDeserializer->fromArray($item, $this->getDeserializeCustomType($item, $metadata) ?? $metadata->type);
                }

                return $array;
            }

            return $value;
        }

        /** @psalm-suppress ArgumentTypeCoercion */
        return $this->jsonDeserializer->fromArray($value, $this->getDeserializeCustomType($value, $metadata) ?? $metadata->type);
    }

    public static function supportsDescribe(string $property, Metadata $metadata): bool
    {
        return true;
    }

    public function describe(string $property, Metadata $metadata): array
    {
        $description = parent::describe($property, $metadata);
        if (is_a($metadata->type, Collection::class, true)
            || Type::BUILTIN_TYPE_ARRAY === $metadata->type) {
            $description['type'] = Type::BUILTIN_TYPE_ARRAY;
            $description['items'] = null;
            if (Serialize::KEYS_VALUES === $metadata->strategy) {
                $description['type'] = Type::BUILTIN_TYPE_OBJECT;
                $description['title'] = 'Custom key-value data.';

                return $description;
            }
            if (null !== $metadata->customType && class_exists($metadata->customType)) {
                $description['title'] = 'Array of ' . SerializerHelper::getClassBaseName($metadata->customType);
                $description['items'] = [
                    'type' => Type::BUILTIN_TYPE_OBJECT,
                    SerializerModelDescriber::NESTED_CLASS => $metadata->customType,
                ];
            }

            return $description;
        }
        $description[SerializerModelDescriber::NESTED_CLASS] = $metadata->type;

        return $description;
    }

    /**
     * @return class-string|null
     *
     * @psalm-suppress LessSpecificReturnStatement
     * @psalm-suppress MoreSpecificReturnType
     */
    private function getDeserializeCustomType(mixed $item, Metadata $metadata): string|null
    {
        if (null !== $metadata->discriminatorMap && array_key_exists(Serialize::DISCRIMINATOR_COLUMN, $item)) {
            return $metadata->discriminatorMap[
                $item[Serialize::DISCRIMINATOR_COLUMN]
            ];
        }

        return $metadata->customType;
    }
}
