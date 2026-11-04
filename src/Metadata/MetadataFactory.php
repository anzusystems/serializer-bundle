<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Metadata;

use AnzuSystems\SerializerBundle\Attributes\Serialize;
use AnzuSystems\SerializerBundle\DependencyInjection\AnzuSystemsSerializerExtension;
use AnzuSystems\SerializerBundle\Exception\SerializerException;
use AnzuSystems\SerializerBundle\Handler\Handlers\BasicHandler;
use Doctrine\Common\Collections\Order;
use PropertyHookType;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionType;
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
        } catch (ReflectionException $exception) { // @phpstan-ignore catch.neverThrown (class-string is not checked at runtime, a missing class still throws)
            throw new SerializerException('Cannot create reflection for ' . $className, 0, $exception);
        }

        $propertyMetadata = $this->buildPropertyMetadata($reflection);

        return $this->buildClassMetadata(
            metadata: array_merge(
                $propertyMetadata,
                $this->buildMethodMetadata($reflection)
            ),
            propertyMetadata: $propertyMetadata,
            reflection: $reflection,
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
            if (true === $propertyMeta?->filledByConstructor && $parameter->getName() === $propertyMeta->property) {
                if ($parameter->isDefaultValueAvailable()) {
                    if (isset($constructorMetadata[$dataName])) {
                        // a required parameter of the same data name keeps it: both cannot take one value
                        continue;
                    }
                    $optionalConstructorArguments[] = $dataName;
                }
                $constructorMetadata[$dataName] = $propertyMeta;

                continue;
            }

            if ($parameter->isDefaultValueAvailable()) {
                // we will use only the required parameters
                continue;
            }

            $constructorMetadata[$dataName] = new Metadata(
                self::resolveTypeName($parameter->getType()),
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
                scalarUnionTypes: self::resolveScalarUnionTypes($parameter->getType(), $attribute),
            );
        }

        return new ClassMetadata(
            metadata: $metadata,
            constructorMetadata: $constructorMetadata,
            optionalConstructorArguments: $optionalConstructorArguments,
        );
    }

    /**
     * @throws SerializerException
     */
    private function buildPropertyMetadata(ReflectionClass $reflection): array
    {
        $metadata = [];
        foreach ($this->collectProperties($reflection) as $property) {
            $attributes = $property->getAttributes(Serialize::class);
            if (false === array_key_exists(0, $attributes)) {
                continue;
            }
            /** @var Serialize $attribute */
            $attribute = $attributes[0]->newInstance();
            $dataName = $attribute->serializedName ?? $property->getName();
            if (isset($metadata[$dataName]) && $property->isPrivate() && $property->getDeclaringClass()->getName() !== $reflection->getName()) {
                continue;
            }
            $metadata[$dataName] = $this->getPropertyMetadata(
                property: $property,
                attribute: $attribute,
                reflection: $reflection,
            );
        }

        return $metadata;
    }

    /**
     * ReflectionClass::getProperties() leaves out the private properties of parent classes.
     *
     * @return array<string, ReflectionProperty>
     */
    private function collectProperties(ReflectionClass $reflection): array
    {
        $properties = [];
        for ($class = $reflection; false !== $class; $class = $class->getParentClass()) {
            foreach ($class->getProperties() as $property) {
                $properties[$property->getName()] ??= $property;
            }
        }

        return $properties;
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
                ?? lcfirst((string) preg_replace('~^(?:get|is)(?=[A-Z])~', '', $method->getName()))
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
        $methodType = $method->getReturnType();

        return new Metadata(
            self::resolveTypeName($methodType),
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
    private function getPropertyMetadata(ReflectionProperty $property, Serialize $attribute, ReflectionClass $reflection): Metadata
    {
        if ($property->isStatic()) {
            throw new SerializerException(
                sprintf('Static property "%s::$%s" cannot be serialized.', $property->getDeclaringClass()->getName(), $property->getName())
            );
        }

        $propertyType = $property->hasHook(PropertyHookType::Set) ? $property->getSettableType() : $property->getType();
        $type = self::resolveTypeName($propertyType);
        $getterPrefix = TypeIdentifier::BOOL->value === $type && $propertyType instanceof ReflectionNamedType ? 'is' : 'get';
        $usesAccessors = false === $property->isPublic() && false === $property->hasHooks();
        $getter = $usesAccessors ? $this->findGetter(reflection: $reflection, property: $property, getterPrefix: $getterPrefix) : null;
        $setter = $usesAccessors ? $this->findPublicMethod($reflection, 'set' . ucfirst($property->getName())) : null;
        $filledByConstructor = $this->isFilledByConstructor($property, $reflection);

        return new Metadata(
            $type,
            (bool) $propertyType?->allowsNull(),
            $getter ?? $property->getName(),
            $property->getName(),
            $this->resolveSetter(
                property: $property,
                getter: $getter,
                setter: $setter,
                filledByConstructor: $filledByConstructor,
            ),
            $attribute->handler,
            $this->resolveCustomType($attribute),
            $attribute->strategy,
            $attribute->persistedName,
            $attribute->discriminatorMap,
            orderBy: self::resolveOrderBy($attribute),
            getterIsMethod: null !== $getter,
            setterIsMethod: null !== $setter,
            declaringClass: $property->getDeclaringClass()
                ->getName(),
            readable: null !== $getter || $this->isPropertyReadable($property),
            filledByConstructor: $filledByConstructor,
            constructorReadonly: $property->isReadOnly() && false === $filledByConstructor && null !== $reflection->getConstructor(),
            readInClassScope: false === $property->isPublic(),
            writeInClassScope: false === $property->isPublic() || $property->isPrivateSet() || $property->isProtectedSet(),
            scalarUnionTypes: self::resolveScalarUnionTypes($propertyType, $attribute),
        );
    }

    private function findGetter(ReflectionClass $reflection, ReflectionProperty $property, string $getterPrefix): ?string
    {
        foreach ([$getterPrefix, 'get'] as $prefix) {
            $getter = $this->findPublicMethod($reflection, $prefix . ucfirst($property->getName()));
            if (null !== $getter) {
                return $getter;
            }
        }

        return null;
    }

    private function findPublicMethod(ReflectionClass $reflection, string $name): ?string
    {
        return $reflection->hasMethod($name) && $reflection->getMethod($name)
            ->isPublic() ? $name : null;
    }

    private function resolveSetter(ReflectionProperty $property, ?string $getter, ?string $setter, bool $filledByConstructor): ?string
    {
        if (null !== $setter) {
            return $setter;
        }
        if (null !== $getter) {
            return null;
        }

        return $this->isPropertyWritable($property, $filledByConstructor) ? $property->getName() : null;
    }

    private function isPropertyReadable(ReflectionProperty $property): bool
    {
        return false === $property->isVirtual() || $property->hasHook(PropertyHookType::Get);
    }

    /**
     * ReflectionProperty::isPromoted() names the constructor that declares the property, which a child class may have
     * overridden, so only the constructor of the deserialized class counts. A private property promoted by a parent is
     * not the child's property of the same name. A readonly property cannot be written after the constructor, so a
     * parameter of its name and type takes the value.
     */
    private function isFilledByConstructor(ReflectionProperty $property, ReflectionClass $reflection): bool
    {
        $constructor = $reflection->getConstructor();
        if (null === $constructor || false === $constructor->isPublic()) {
            return false;
        }

        foreach ($constructor->getParameters() as $parameter) {
            if ($property->getName() !== $parameter->getName()) {
                continue;
            }
            if (false === $parameter->isPromoted()) {
                return $property->isReadOnly() && (string) $parameter->getType() === (string) $property->getType();
            }

            $promotingClass = $constructor->getDeclaringClass();

            return $promotingClass->getName() === $property->getDeclaringClass()
                ->getName()
                || false === $promotingClass->getProperty($property->getName())
                    ->isPrivate();
        }

        return false;
    }

    private function isPropertyWritable(ReflectionProperty $property, bool $filledByConstructor): bool
    {
        if ($filledByConstructor) {
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
                return (string) $this->parameterBag->get($paramName);
            }

            throw new SerializerException(
                'The parameter `' . $paramName . '` not found in `'
                . AnzuSystemsSerializerExtension::SERIALIZER_PARAMETER_BAG_ID . '` configuration.'
            );
        }

        return $attribute->type;
    }

    private static function resolveTypeName(?ReflectionType $type): string
    {
        if ($type instanceof ReflectionNamedType) {
            return $type->getName();
        }
        if ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $unionType) {
                if ($unionType instanceof ReflectionNamedType && 'null' !== $unionType->getName()) {
                    return $unionType->getName();
                }
            }
        }

        return '';
    }

    /**
     * A handler serves one type, so it would turn the int 5 of an int|string into "5". A class in the union, a custom
     * handler or a custom type keeps the handler, because they turn the input into something else.
     *
     * @return list<string>
     */
    private static function resolveScalarUnionTypes(?ReflectionType $type, Serialize $attribute): array
    {
        if (false === $type instanceof ReflectionUnionType || null !== $attribute->handler || false === empty($attribute->type)) {
            return [];
        }

        $names = [];
        foreach ($type->getTypes() as $unionType) {
            $name = $unionType instanceof ReflectionNamedType ? $unionType->getName() : '';
            if ('null' === $name) {
                continue;
            }
            if (false === in_array($name, [...BasicHandler::BASIC_TYPES, TypeIdentifier::ARRAY->value], true)) {
                return [];
            }
            $names[] = $name;
        }

        return count($names) > 1 ? $names : [];
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
        foreach ($this->collectProperties($reflection) as $property) {
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
