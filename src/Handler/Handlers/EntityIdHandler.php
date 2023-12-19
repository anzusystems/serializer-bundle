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
use ReflectionNamedType;
use Symfony\Component\PropertyInfo\Type;
use Symfony\Component\Uid\Uuid;

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
        $toIdFunction = fn (object $item): null|int|string|object => $item->getId();
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
            if ($metadata->orderBy) {
                $ids = $this->getOrderedIDs($ids->getValues(), $metadata);
            }
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
                /** @psalm-suppress ArgumentTypeCoercion */
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
        $deserializeType = $metadata->customType ?? $metadata->type;
        /** @psalm-suppress ArgumentTypeCoercion */
        if (method_exists($deserializeType, 'getId') && (is_int($value) || is_string($value))) {
            return $this->entityManager->find($deserializeType, $value);
        }

        throw new SerializerException('Unsupported value for ' . self::class . '::' . __FUNCTION__);
    }

    public function describe(string $property, Metadata $metadata): array
    {
        $description = parent::describe($property, $metadata);
        if (is_a($metadata->type, Collection::class, true)
            || Type::BUILTIN_TYPE_ARRAY === $metadata->type) {
            $description['type'] = Type::BUILTIN_TYPE_ARRAY;
            $description['title'] = SerializerHelper::getClassBaseName((string) $metadata->customType) . ' IDs';
            $description['items'] = ['type' => $this->describeReturnType((string) $metadata->customType)];

            return $description;
        }

        $description['type'] = $this->describeReturnType($metadata->type);
        $description['title'] = SerializerHelper::getClassBaseName($metadata->type) . ' ID';

        return $description;
    }

    private function getOrderedIDs(array $ids, Metadata $metadata): Collection
    {
        $uuids = false;
        $ids = array_map(function (null|int|string|object $id) use (&$uuids) {
            if (class_exists(Uuid::class) && $id instanceof Uuid) {
                $uuids = true;

                return $id->toBinary();
            }

            return $id;
        }, $ids);
        /** @psalm-suppress ArgumentTypeCoercion */
        $dqb = $this->entityManager->getRepository((string) $metadata->customType)->createQueryBuilder('entity');
        $dqb
            ->select('entity.id')
            ->where('entity.id IN (:ids)')
            ->setParameter('ids', $ids)
        ;
        if (null !== $metadata->orderBy) {
            foreach ($metadata->orderBy as $field => $direction) {
                $dqb->addOrderBy('entity.' . $field, $direction);
            }
        }
        $resultIds = array_map(function (null|int|string $id) use ($uuids) {
            if (class_exists(Uuid::class) && $uuids) {
                return Uuid::fromString((string) $id);
            }

            return $id;
        }, $dqb->getQuery()->getSingleColumnResult());

        return new ArrayCollection($resultIds);
    }

    private function describeReturnType(string $type): string
    {
        try {
            /** @psalm-suppress ArgumentTypeCoercion */
            $reflection = new ReflectionMethod($type, 'getId');
        } catch (ReflectionException $e) {
            return $type;
        }

        if ($reflection->getReturnType() instanceof ReflectionNamedType) {
            return SerializerHelper::getOaFriendlyType($reflection->getReturnType()->getName());
        }

        return SerializerHelper::getOaFriendlyType($type);
    }
}
