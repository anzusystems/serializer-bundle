<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Metadata;

use AnzuSystems\SerializerBundle\Attributes\Serialize;
use AnzuSystems\SerializerBundle\DependencyInjection\AnzuSystemsSerializerExtension;
use AnzuSystems\SerializerBundle\Exception\SerializerException;
use Doctrine\Common\Collections\Order;
use PropertyHookType;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\TypeInfo\TypeIdentifier;

final readonly class MetadataFactory
{
    public function __construct(
        private ParameterBagInterface $parameterBag
    ) {
    }

    /**
     * @param class-string $className
     *
     * @throws SerializerException
     */
    public function buildMetadata(string $className): ClassMetadata
    {
        try {
            $reflection = new ReflectionClass($className);
        } catch (ReflectionException $exception) {
            throw new SerializerException('Cannot create reflection for ' . $className, 0, $exception);
        }

        $propertyMetadata = $this->buildPropertyMetadata($reflection);

        return $this->buildClassMetadata(
            array_merge(
                $propertyMetadata,
                $this->buildMethodMetadata($reflection)
            ),
            $propertyMetadata,
            $reflection,
        );
    }

    /**
     * @param array<string, Metadata> $metadata
     * @param array<string, Metadata> $propertyMetadata
     *
     * @throws SerializerException
     */
    private function buildClassMetadata(array $metadata, array $propertyMetadata, ReflectionClass $reflection): ClassMetadata
    {
        $constructorMethod = $reflection->getConstructor();
        if (null === $constructorMethod) {
            // the class has no constructor
            return new ClassMetadata($metadata, []);
        }

        if (0 === ($constructorMethod->getModifiers() & ReflectionMethod::IS_PUBLIC)) {
            // the class has private/protected constructor
            return new ClassMetadata($metadata, []);
        }

        $constructorMetadata = [];
        $optionalConstructorArguments = [];
        foreach ($constructorMethod->getParameters() as $parameter) {
            $attribute = $this->findRelatedClassPropertyAttribute($reflection, $parameter->getName());

            if (null === $attribute) {
                // accept if the constructor has a property that should not be serialized
                // because the object may only be used for serialization and not deserialization
                $attribute = new Serialize();
            }

            $dataName = $attribute->serializedName ?? $parameter->getName();
            $propertyMeta = $propertyMetadata[$dataName] ?? null;
            if ($parameter->isPromoted() && $propertyMeta) {
                $constructorMetadata[$dataName] = $propertyMeta;
                if ($parameter->isDefaultValueAvailable()) {
                    $optionalConstructorArguments[] = $dataName;
                }

                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                // we will use only the required parameters
                continue;
            }

            $constructorMetadata[$dataName] = new Metadata(
                (string) $parameter->getType(),
                $parameter->allowsNull(),
                '',
                $parameter->getName(),
                null,
                $attribute->handler,
                $this->resolveCustomType($attribute),
                $attribute->strategy,
                $attribute->persistedName,
                $attribute->discriminatorMap,
                orderBy: self::resolveOrderBy($attribute),
            );
        }

        return new ClassMetadata($metadata, $constructorMetadata, $optionalConstructorArguments);
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
            $metadata[$dataName] = $this->getPropertyMetadata($property, $attribute);
        }

        return $metadata;
    }

    /**
     * @throws SerializerException
     */
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
                ?? lcfirst((string) preg_replace('~^[get|is]*(.+)~', '$1', $method->getName()))
            ;
            $metadata[$dataName] = $this->getMethodMetadata($method, $attribute);
        }

        return $metadata;
    }

    /**
     * @throws SerializerException
     */
    private function getMethodMetadata(ReflectionMethod $method, Serialize $attribute): Metadata
    {
        $type = '';
        $methodType = $method->getReturnType();
        if ($methodType instanceof ReflectionNamedType) {
            $type = $methodType->getName();
        }

        if ($methodType instanceof ReflectionUnionType) {
            foreach ($methodType->getTypes() as $returnType) {
                if ($returnType instanceof ReflectionNamedType) {
                    if ('null' === $returnType->getName()) {
                        continue;
                    }
                    $type = $returnType->getName();

                    break;
                }
            }
        }

        return new Metadata(
            $type,
            (bool) $methodType?->allowsNull(),
            $method->getName(),
            null,
            null,
            $attribute->handler,
            $this->resolveCustomType($attribute),
            $attribute->strategy,
            orderBy: self::resolveOrderBy($attribute),
        );
    }

    /**
     * @throws SerializerException
     */
    private function getPropertyMetadata(ReflectionProperty $property, Serialize $attribute): Metadata
    {
        $getterPrefix = 'get';
        $propertyType = $property->getType();
        $type = '';
        if ($propertyType instanceof ReflectionNamedType) {
            $type = $propertyType->getName();
            if (TypeIdentifier::BOOL->value === $type) {
                $getterPrefix = 'is';
            }
        }
        if ($propertyType instanceof ReflectionUnionType) {
            foreach ($propertyType->getTypes() as $returnType) {
                if ($returnType instanceof ReflectionNamedType) {
                    if ('null' === $returnType->getName()) {
                        continue;
                    }
                    $type = $returnType->getName();

                    break;
                }
            }
        }
        $getter = $this->findGetter($property, $getterPrefix);
        $getterSetterStrategy = is_string($getter) && false === $property->isPublic() && false === $property->hasHooks();

        return new Metadata(
            $type,
            (bool) $propertyType?->allowsNull(),
            $getterSetterStrategy ? (string) $getter : $property->getName(),
            $property->getName(),
            $this->resolveSetter($property, $getterSetterStrategy),
            $attribute->handler,
            $this->resolveCustomType($attribute),
            $attribute->strategy,
            $attribute->persistedName,
            $attribute->discriminatorMap,
            orderBy: self::resolveOrderBy($attribute),
            getterSetterStrategy: $getterSetterStrategy,
            declaringClass: $property->getDeclaringClass()
                ->getName(),
            readable: $getterSetterStrategy || $this->isPropertyReadable($property),
            promoted: $property->isPromoted(),
        );
    }

    private function findGetter(ReflectionProperty $property, string $getterPrefix): ?string
    {
        $declaringClass = $property->getDeclaringClass();
        foreach ([$getterPrefix, 'get'] as $prefix) {
            $getter = $prefix . ucfirst($property->getName());
            if ($declaringClass->hasMethod($getter)) {
                return $getter;
            }
        }

        return null;
    }

    private function resolveSetter(ReflectionProperty $property, bool $getterSetterStrategy): ?string
    {
        if ($getterSetterStrategy) {
            $setter = 'set' . ucfirst($property->getName());

            return $property->getDeclaringClass()
                ->hasMethod($setter) ? $setter : null;
        }

        return $this->isPropertyWritable($property) ? $property->getName() : null;
    }

    private function isPropertyReadable(ReflectionProperty $property): bool
    {
        return false === $property->isVirtual() || $property->hasHook(PropertyHookType::Get);
    }

    /**
     * A promoted property is filled by the constructor, and a readonly one cannot be written again.
     */
    private function isPropertyWritable(ReflectionProperty $property): bool
    {
        if ($property->isPromoted()) {
            return false;
        }

        return false === $property->isVirtual() || $property->hasHook(PropertyHookType::Set);
    }

    /**
     * @throws SerializerException
     */
    private function resolveCustomType(Serialize $attribute): ?string
    {
        if ('' === $attribute->type) {
            return null;
        }

        if ($attribute->type instanceof ContainerParam) {
            $paramName = $attribute->type->paramName;
            if ($this->parameterBag->has($paramName)) {
                /** @psalm-suppress PossiblyInvalidCast */
                return (string) $this->parameterBag->get($paramName);
            }

            throw new SerializerException(
                'The parameter `' . $paramName . '` not found in `'
                . AnzuSystemsSerializerExtension::SERIALIZER_PARAMETER_BAG_ID . '` configuration.'
            );
        }

        return $attribute->type;
    }

    /**
     * @return null|array<string, 'ASC'|'DESC'>
     */
    private static function resolveOrderBy(Serialize $attribute): ?array
    {
        if (null === $attribute->orderBy) {
            return null;
        }

        return array_map(
            static fn (Order|string $direction): string => $direction instanceof Order ? $direction->value : $direction,
            $attribute->orderBy,
        );
    }

    /**
     * @throws SerializerException
     */
    private function findRelatedClassPropertyAttribute(ReflectionClass $reflection, string $name): ?Serialize
    {
        foreach ($reflection->getProperties() as $property) {
            if ($property->getName() !== $name) {
                continue;
            }

            $attributes = $property->getAttributes(Serialize::class);
            if (false === array_key_exists(0, $attributes)) {
                return null;
            }

            return $attributes[0]->newInstance();
        }

        throw new SerializerException(
            sprintf(
                'Required constructor property "%s" not found as parameter in the class "%s".',
                $name,
                $reflection->getName()
            )
        );
    }
}
