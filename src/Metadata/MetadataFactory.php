<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Metadata;

use AnzuSystems\SerializerBundle\Attributes\Serialize;
use AnzuSystems\SerializerBundle\Exception\SerializerException;
use Doctrine\ORM\EntityManagerInterface;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;
use Symfony\Component\PropertyInfo\Type;

final class MetadataFactory
{
    public function __construct(
        private readonly ?EntityManagerInterface $entityManager = null,
    ) {
    }

    /**
     * @param class-string $className
     *
     * @throws SerializerException
     */
    public function buildMetadata(string $className): array
    {
        try {
            $reflection = new ReflectionClass($className);
            if ((int) $reflection->getConstructor()?->getNumberOfRequiredParameters() > 0) {
                throw new SerializerException('Required constructor parameters found in ' . $className);
            }
        } catch (ReflectionException $exception) {
            throw new SerializerException('Cannot create reflection for ' . $className, 0, $exception);
        }

        return array_merge(
            $this->buildPropertyMetadata($reflection),
            $this->buildMethodMetadata($reflection)
        );
    }

    /**
     * @throws SerializerException
     */
    private function buildPropertyMetadata(ReflectionClass $reflection): array
    {
        $metadata = [];
        foreach ($reflection->getProperties() as $property) {
            $attributes = $property->getAttributes(Serialize::class);
            if (false === array_key_exists(0, $attributes)) {
                continue;
            }
            /** @var Serialize $attribute */
            $attribute = $attributes[0]->newInstance();
            $dataName = $attribute->serializedName ?? $property->getName();
            $metadata[$dataName] = $this->getPropertyMetadata($reflection, $property, $attribute);
        }

        return $metadata;
    }

    private function buildMethodMetadata(ReflectionClass $reflection): array
    {
        $metadata = [];
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            $attributes = $method->getAttributes(Serialize::class);
            if (false === array_key_exists(0, $attributes)) {
                continue;
            }
            /** @var Serialize $attribute */
            $attribute = $attributes[0]->newInstance();
            $dataName = $attribute->serializedName
                ?? lcfirst(preg_replace('~^[get|is]*(.+)~', '$1', $method->getName()))
            ;
            $metadata[$dataName] = $this->getMethodMetadata($method, $attribute);
        }

        return $metadata;
    }

    private function getMethodMetadata(ReflectionMethod $method, Serialize $attribute): Metadata
    {
        $type = '';
        $methodType = $method->getReturnType();
        if ($methodType instanceof ReflectionNamedType) {
            $type = $methodType->getName();
        }
        if ($methodType instanceof ReflectionUnionType) {
            foreach ($methodType->getTypes() as $returnType) {
                if ('null' === $returnType->getName()) {
                    continue;
                }
                $type = $returnType->getName();
                break;
            }
        }

        return new Metadata(
            $type,
            (bool) $methodType?->allowsNull(),
            $method->getName(),
            null,
            null,
            $attribute->handler,
            $attribute->type,
            $attribute->strategy,
        );
    }

    /**
     * @throws SerializerException
     */
    private function getPropertyMetadata(ReflectionClass $class, ReflectionProperty $property, Serialize $attribute): Metadata
    {
        $getterPrefix = 'get';
        $propertyType = $property->getType();
        $type = '';
        if ($propertyType instanceof ReflectionNamedType) {
            $type = $this->inferNamedPropertyTypeForClass($class, $property, $attribute);
            if (Type::BUILTIN_TYPE_BOOL === $type) {
                $getterPrefix = 'is';
            }
        }
        if ($propertyType instanceof ReflectionUnionType) {
            foreach ($propertyType->getTypes() as $returnType) {
                if ('null' === $returnType->getName()) {
                    continue;
                }
                $type = $returnType->getName();
                break;
            }
        }
        $getter = $getterPrefix . ucfirst($property->getName());
        if (false === $property->getDeclaringClass()->hasMethod($getter)) {
            throw new SerializerException('Getter method ' . $getter . ' not found in ' . $property->getDeclaringClass()->getName() . '.');
        }
        $setter = 'set' . ucfirst($property->getName());
        if (false === $property->getDeclaringClass()->hasMethod($getter)) {
            throw new SerializerException('Setter method ' . $setter . ' not found in ' . $property->getDeclaringClass()->getName() . '.');
        }

        return new Metadata(
            $type,
            (bool) $propertyType?->allowsNull(),
            $getter,
            $property->getName(),
            $setter,
            $attribute->handler,
            $attribute->type,
            $attribute->strategy,
            $attribute->persistedName,
        );
    }

    /**
     * Infer type for named property.
     * In case the class where property belongs is Doctrine's entity associated field, use a target entity as the type.
     */
    private function inferNamedPropertyTypeForClass(ReflectionClass $class, ReflectionProperty $property, Serialize $attribute): string
    {
        $type = $property->getType()->getName();
        $className = $class->getName();
        $propertyName = $property->getName();
        if (false === ($this->entityManager instanceof EntityManagerInterface)) {
            return $type;
        }
        if (false === $this->entityManager->getMetadataFactory()->hasMetadataFor($className)) {
            return $type;
        }
        $classMetadata = $this->entityManager->getClassMetadata($className);
        if (false === $classMetadata->hasAssociation($propertyName)) {
            return $type;
        }
        $targetEntityType = $classMetadata->getAssociationMapping($propertyName)['targetEntity'];
        if (is_a($type, 'Doctrine\Common\Collections\Collection', true)) {
            $attribute->type = $targetEntityType;

            return $type;
        }

        return $targetEntityType;
    }
}
