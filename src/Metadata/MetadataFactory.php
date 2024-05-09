<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Metadata;

use AnzuSystems\SerializerBundle\Attributes\Serialize;
use AnzuSystems\SerializerBundle\DependencyInjection\AnzuSystemsSerializerExtension;
use AnzuSystems\SerializerBundle\Exception\SerializerException;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\PropertyInfo\Type;

final class MetadataFactory
{
    public function __construct(
        private readonly ParameterBagInterface $parameterBag
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

        return new ClassMetadata(
            array_merge(
                $this->buildPropertyMetadata($reflection),
                $this->buildMethodMetadata($reflection)
            ),
            $this->buildConstructorMetadata($reflection),
        );
    }

    /**
     * @throws SerializerException
     */
    private function buildConstructorMetadata(ReflectionClass $reflection): array
    {
        $constructorMethod = $reflection->getConstructor();
        if (null === $constructorMethod) {
            // the class has no constructor
            return [];
        }

        if (!($constructorMethod->getModifiers() & ReflectionMethod::IS_PUBLIC)) {
            // the class has private/protected constructor
            return [];
        }

        $attribute = new Serialize();

        $metadata = [];
        foreach ($constructorMethod->getParameters() as $parameter) {
            if ($parameter->isDefaultValueAvailable()) {
                // we will use only the required parameters
                continue;
            }

            $dataName = $attribute->serializedName ?? $parameter->getName();
            $metadata[$dataName] = new Metadata(
                (string) $parameter->getType(),
                $parameter->allowsNull(),
                '',
                $parameter->getName(),
            );
        }

        return $metadata;
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
                ?? lcfirst(preg_replace('~^[get|is]*(.+)~', '$1', $method->getName()))
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
            $this->resolveCustomType($attribute),
            $attribute->strategy,
            orderBy: $attribute->orderBy
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
        $declaringClass = $property->getDeclaringClass();
        if (false === $declaringClass->hasMethod($getter)) {
            // fallback to "get" prefix
            $getterFallback = 'get' . ucfirst($property->getName());
            if (false === $declaringClass->hasMethod($getterFallback)) {
                throw new SerializerException('Getter method ' . $getter . ' or ' . $getterFallback . ' not found in ' . $declaringClass->getName() . '.');
            }

            $getter = $getterFallback;
        }
        $setter = 'set' . ucfirst($property->getName());
        if (false === $declaringClass->hasMethod($setter)) {
            throw new SerializerException('Setter method ' . $setter . ' not found in ' . $declaringClass->getName() . '.');
        }

        return new Metadata(
            $type,
            (bool) $propertyType?->allowsNull(),
            $getter,
            $property->getName(),
            $setter,
            $attribute->handler,
            $this->resolveCustomType($attribute),
            $attribute->strategy,
            $attribute->persistedName,
            $attribute->discriminatorMap,
            orderBy: $attribute->orderBy
        );
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
}
