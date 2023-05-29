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
            $this->resolveCustomType($attribute),
            $attribute->strategy,
            $attribute->persistedName,
            $attribute->discriminatorMap,
        );
    }

    /**
     * @throws SerializerException
     */
    private function resolveCustomType(Serialize $attribute): ?string
    {
        if ($attribute->type instanceof ContainerParam) {
            $paramName = $attribute->type->paramName;
            if ($this->parameterBag->has($paramName)) {
                return (string) $this->parameterBag->get($paramName);
            }

            throw new SerializerException('The parameter `' . $paramName . '` not found in `'
                . AnzuSystemsSerializerExtension::SERIALIZER_PARAMETER_BAG_ID . '` configuration.'
            );
        }

        return $attribute->type;
    }
}
