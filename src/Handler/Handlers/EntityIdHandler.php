<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Handler\Handlers;

use AnzuSystems\SerializerBundle\Attributes\Serialize;
use AnzuSystems\SerializerBundle\Context\SerializationContext;
use AnzuSystems\SerializerBundle\Exception\SerializerException;
use AnzuSystems\SerializerBundle\Handler\BatchItem;
use AnzuSystems\SerializerBundle\Helper\SerializerHelper;
use AnzuSystems\SerializerBundle\Metadata\Metadata;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata as DoctrineClassMetadata;
use Doctrine\Persistence\Mapping\MappingException;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use Symfony\Component\TypeInfo\TypeIdentifier;
use Symfony\Component\Uid\Uuid;

final class EntityIdHandler extends AbstractHandler implements BatchDeserializeHandlerInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function prepareDeserializeBatch(BatchItem ...$items): void
    {
        $idsByEntityClass = [];
        foreach ($items as $item) {
            $entityClass = (string) ($item->metadata->customType ?? $item->metadata->type);
            $value = $item->value;
            foreach (is_iterable($value) ? $value : [$value] as $id) {
                $idsByEntityClass[$entityClass][] = $id;
            }
        }

        foreach ($idsByEntityClass as $entityClass => $ids) {
            $this->preloadEntities($ids, $entityClass);
        }
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
            $entities = $this->loadEntities(iterator_to_array($value, preserve_keys: false), $entityClass);
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
     * The deserializer runs prepareDeserializeBatch() before it asks for any property, so an id the identity map
     * does not know is one the database does not have - a find() fallback would only repeat the query that missed.
     *
     * @param list<mixed> $ids
     *
     * @return list<object>
     */
    private function loadEntities(array $ids, string $entityClass): array
    {
        /** @psalm-suppress ArgumentTypeCoercion */
        $classMetadata = $this->entityManager->getClassMetadata($entityClass);

        $entities = [];
        foreach ($ids as $id) {
            /** @psalm-suppress ArgumentTypeCoercion */
            $entity = is_int($id) || is_string($id)
                ? $this->tryGetManaged($id, $classMetadata)
                : $this->entityManager->find($entityClass, $id);
            if (null === $entity || false === $entity instanceof $entityClass) {
                continue;
            }

            $entities[] = $entity;
        }

        return $entities;
    }

    /**
     * @param list<mixed> $ids
     */
    private function preloadEntities(array $ids, string $entityClass): void
    {
        try {
            /** @psalm-suppress ArgumentTypeCoercion */
            $classMetadata = $this->entityManager->getClassMetadata($entityClass);
        } catch (MappingException) {
            // The handler also serves value objects that merely carry a getId(), and warming up is never worth
            // breaking a payload over.
            return;
        }

        $unmanagedIds = [];
        foreach ($ids as $id) {
            if (false === is_int($id) && false === is_string($id)) {
                continue;
            }
            if (null === $this->tryGetManaged($id, $classMetadata)) {
                $unmanagedIds[$id] = $id;
            }
        }
        if ([] === $unmanagedIds) {
            return;
        }

        /** @psalm-suppress ArgumentTypeCoercion */
        $this->entityManager->getRepository($entityClass)
            ->findBy([$classMetadata->getSingleIdentifierFieldName() => $unmanagedIds]);
    }

    private function tryGetManaged(int|string $id, DoctrineClassMetadata $classMetadata): ?object
    {
        /** @psalm-suppress ArgumentTypeCoercion */
        $entity = $this->entityManager->getUnitOfWork()
            ->tryGetById(
                [$classMetadata->getSingleIdentifierFieldName() => $id],
                $classMetadata->rootEntityName,
            );

        return is_object($entity) ? $entity : null;
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
