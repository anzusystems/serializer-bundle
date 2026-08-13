<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Handler\Handlers;

use AnzuSystems\SerializerBundle\Attributes\Serialize;
use AnzuSystems\SerializerBundle\Context\SerializationContext;
use AnzuSystems\SerializerBundle\Exception\SerializerException;
use AnzuSystems\SerializerBundle\Helper\SerializerHelper;
use AnzuSystems\SerializerBundle\Metadata\Metadata;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use Symfony\Component\TypeInfo\TypeIdentifier;
use Symfony\Component\Uid\Uuid;

final class EntityIdHandler extends AbstractHandler
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function serialize(mixed $value, Metadata $metadata, SerializationContext $context): array|object|int|null|string
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
            if (null !== $metadata->orderBy) {
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
            $entityClass = (string) $metadata->customType;
            $ids = [];
            foreach ($value as $id) {
                $ids[] = $id;
            }
            $absentIds = $this->preloadEntities($ids, $entityClass);

            $entities = [];
            foreach ($ids as $id) {
                if ((is_int($id) || is_string($id)) && isset($absentIds[$id])) {
                    continue;
                }

                /** @psalm-suppress ArgumentTypeCoercion */
                $entity = $this->entityManager->find($entityClass, $id);
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
            || TypeIdentifier::ARRAY->value === $metadata->type) {
            $description['type'] = TypeIdentifier::ARRAY->value;
            $description['title'] = SerializerHelper::getClassBaseName((string) $metadata->customType) . ' IDs';
            $description['items'] = ['type' => $this->describeReturnType((string) $metadata->customType)];

            return $description;
        }

        $description['type'] = $this->describeReturnType($metadata->type);
        $description['title'] = SerializerHelper::getClassBaseName($metadata->type) . ' ID';

        return $description;
    }

    /**
     * One query for the whole list, so the find() calls that follow hit the identity map. Ids it did not bring
     * back are returned, because Doctrine has no negative cache and find() would query each of them again.
     *
     * @param list<mixed> $ids
     *
     * @return array<int|string, int>
     */
    private function preloadEntities(array $ids, string $entityClass): array
    {
        /** @psalm-suppress ArgumentTypeCoercion */
        $classMetadata = $this->entityManager->getClassMetadata($entityClass);
        $identifier = $classMetadata->getSingleIdentifierFieldName();
        $rootEntityName = $classMetadata->rootEntityName;

        $unmanagedIds = $this->filterUnmanagedIds($ids, $identifier, $rootEntityName);
        if (count($unmanagedIds) < 2) {
            return [];
        }

        /** @psalm-suppress ArgumentTypeCoercion */
        $this->entityManager->getRepository($entityClass)
            ->findBy([$identifier => $unmanagedIds]);

        return array_flip($this->filterUnmanagedIds($unmanagedIds, $identifier, $rootEntityName));
    }

    /**
     * @param list<mixed> $ids
     *
     * @return list<int|string> without duplicates
     */
    private function filterUnmanagedIds(array $ids, string $identifier, string $rootEntityName): array
    {
        $unitOfWork = $this->entityManager->getUnitOfWork();

        $unmanagedIds = [];
        foreach ($ids as $id) {
            if (false === is_int($id) && false === is_string($id)) {
                continue;
            }
            // The same lookup find() does, so an id it answers from the identity map is never queried again.
            /** @psalm-suppress ArgumentTypeCoercion */
            if (false === $unitOfWork->tryGetById([$identifier => $id], $rootEntityName)) {
                $unmanagedIds[$id] = $id;
            }
        }

        return array_values($unmanagedIds);
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
        $dqb = $this->entityManager->getRepository((string) $metadata->customType)
            ->createQueryBuilder('entity');
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
            /** @psalm-suppress TypeDoesNotContainType */
            if (class_exists(Uuid::class) && $uuids) {
                return Uuid::fromString((string) $id);
            }

            return $id;
        }, $dqb->getQuery()
            ->getSingleColumnResult());

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
