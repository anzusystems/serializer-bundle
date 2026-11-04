<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Service;

use AnzuSystems\SerializerBundle\Exception\SerializerException;
use AnzuSystems\SerializerBundle\Metadata\Metadata;
use Closure;
use Error;
use ReflectionException;
use ReflectionProperty;

final class PropertyAccessor
{
    /**
     * @var array<string, Closure>
     */
    private array $readers = [];

    /**
     * @var array<string, Closure>
     */
    private array $writers = [];

    /**
     * @throws SerializerException
     */
    public function getValue(object $object, Metadata $metadata): mixed
    {
        try {
            return $this->read($object, $metadata);
        } catch (Error $error) {
            if ($this->isUninitialized($object, $metadata)) {
                throw new SerializerException(
                    sprintf('Unable to serialize "%s::$%s". The property is not initialized.', $metadata->declaringClass, $metadata->property),
                    0,
                    $error,
                );
            }

            throw $error;
        }
    }

    /**
     * @throws ReflectionException
     * @throws SerializerException
     */
    public function setValue(object $object, Metadata $metadata, mixed $value): void
    {
        if ($metadata->setterIsMethod) {
            $object->{(string) $metadata->setter}($value);

            return;
        }

        [$declaringClass, $property] = $this->resolveProperty($metadata);
        $writer = $this->writers[$declaringClass . '::' . $property] ??= $this->createWriter($declaringClass, $property);

        $writer($object, $value);
    }

    /**
     * @throws Error when the property is not initialized
     * @throws SerializerException
     */
    private function read(object $object, Metadata $metadata): mixed
    {
        if ($metadata->getterIsMethod) {
            return $object->{$metadata->getter}();
        }

        [$declaringClass, $property] = $this->resolveProperty($metadata);
        $reader = $this->readers[$declaringClass . '::' . $property] ??= $this->bindToClass(
            static fn (object $object): mixed => $object->{$property},
            $declaringClass,
        );

        return $reader($object);
    }

    private function isUninitialized(object $object, Metadata $metadata): bool
    {
        if (null === $metadata->declaringClass || null === $metadata->property) {
            return false;
        }

        return false === (new ReflectionProperty($metadata->declaringClass, $metadata->property))->isInitialized($object);
    }

    /**
     * PHP refuses to write a readonly property twice, so a value the constructor already set wins over the input.
     *
     * @param class-string $declaringClass
     *
     * @throws ReflectionException
     * @throws SerializerException
     */
    private function createWriter(string $declaringClass, string $property): Closure
    {
        $reflection = new ReflectionProperty($declaringClass, $property);
        $isReadOnly = $reflection->isReadOnly();

        return $this->bindToClass(
            static function (object $object, mixed $value) use ($property, $reflection, $isReadOnly): void {
                if ($isReadOnly && $reflection->isInitialized($object)) {
                    return;
                }
                $object->{$property} = $value;
            },
            $declaringClass,
        );
    }

    /**
     * @param class-string $declaringClass
     *
     * @throws SerializerException
     */
    private function bindToClass(Closure $accessor, string $declaringClass): Closure
    {
        $boundAccessor = Closure::bind($accessor, newThis: null, newScope: $declaringClass);
        if (null === $boundAccessor) {
            throw new SerializerException(sprintf('Cannot bind a property accessor to the class "%s".', $declaringClass));
        }

        return $boundAccessor;
    }

    /**
     * @return array{class-string, string}
     *
     * @throws SerializerException
     */
    private function resolveProperty(Metadata $metadata): array
    {
        if (null === $metadata->declaringClass || null === $metadata->property) {
            throw new SerializerException('Property metadata without a declaring class or property name.');
        }

        return [$metadata->declaringClass, $metadata->property];
    }
}
