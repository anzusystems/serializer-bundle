<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Handler\Handlers;

use AnzuSystems\SerializerBundle\Attributes\Serialize;
use AnzuSystems\SerializerBundle\Exception\SerializerException;
use AnzuSystems\SerializerBundle\Helper\SerializerHelper;
use AnzuSystems\SerializerBundle\Metadata\Metadata;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use ReflectionException;
use ReflectionMethod;
use Symfony\Component\PropertyInfo\Type;

final class EntityIdHandler extends AbstractHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function serialize(mixed $value, Metadata $metadata): array|object|int|null|string
    {
        if (null === $value) {
            return null;
        }
        $toIdFunction = fn (object $item): null|int|string => $item->getId();
        if (is_array($value)) {
            $ids = array_map($toIdFunction, $value);
            if (Serialize::KEYS_VALUES === $metadata->strategy) {
                if (empty($ids)) {
                    return new \stdClass();
                }
                return $ids;
            }

            return array_values($ids);
        }
        if ($value instanceof Collection) {
            $ids = $value->map($toIdFunction);
            if (Serialize::KEYS_VALUES === $metadata->strategy) {
                if ($ids->isEmpty()) {
                    return new \stdClass();
                }
                return $ids->toArray();
            }

            return $ids->getValues();
        }
        if (method_exists($value, 'getId')) {
            return $toIdFunction($value);
        }

        throw new SerializerException('Unsupported value for ' . self::class . '::' . __FUNCTION__);
    }

    public function deserialize(mixed $value, Metadata $metadata): mixed
    {
        if (null === $value) {
            return null;
        }
        if (is_iterable($value)) {
            $entities = [];
            foreach ($value as $id) {
                $entity = $this->entityManager->find((string) $metadata->customType, $id);
                if ($entity) {
                    $entities[] = $entity;
                }
            }
            if (is_a($metadata->type, Collection::class, true)) {
                return new ArrayCollection($entities);
            }

            return $entities;
        }
        if (method_exists($metadata->type, 'getId') && (is_int($value) || is_string($value))) {
            return $this->entityManager->find($metadata->type, $value);
        }

        throw new SerializerException('Unsupported value for ' . self::class . '::' . __FUNCTION__);
    }

    public function describe(string $property, Metadata $metadata): array
    {
        $description = parent::describe($property, $metadata);
        if (is_a($metadata->type, Collection::class, true)
            || Type::BUILTIN_TYPE_ARRAY === $metadata->type) {
            $description['type'] = Type::BUILTIN_TYPE_ARRAY;
            $description['title'] = SerializerHelper::getClassBaseName($metadata->customType) . ' IDs';
            $description['items'] = ['type' => $this->describeReturnType($metadata->customType)];

            return $description;
        }

        $description['type'] = $this->describeReturnType($metadata->type);
        $description['title'] = SerializerHelper::getClassBaseName($metadata->type) . ' ID';

        return $description;
    }

    private function describeReturnType(string $type): string
    {
        try {
            $reflection = new ReflectionMethod($type, 'getId');
        } catch (ReflectionException $e) {
            return $type;
        }

        return SerializerHelper::getOaFriendlyType($reflection->getReturnType()?->getName() ?? $type);
    }
}
