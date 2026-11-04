<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Service;

use AnzuSystems\SerializerBundle\Exception\SerializerException;
use AnzuSystems\SerializerBundle\Metadata\Metadata;
use Closure;
use ReflectionProperty;

final class PropertyAccessor
{
    /**
     * @var array<string, array<string, Closure>>
     */
    private array $readers = [];

    /**
     * @var array<string, array<string, Closure>>
     */
    private array $writers = [];

    /**
     * @throws SerializerException
     */
    public function getValue(object $object, Metadata $metadata): mixed
    {
        if ($metadata->getterIsMethod) {
            return $object->{$metadata->getter}();
        }

        if (false === $metadata->readInClassScope) {
            return $object->{$metadata->getter};
        }

        $reader = $this->readers[$metadata->declaringClass][$metadata->property] ?? $this->createReader($metadata);

        return $reader($object);
    }

    /**
     * @throws SerializerException
     */
    public function setValue(object $object, Metadata $metadata, mixed $value): void
    {
        if ($metadata->setterIsMethod) {
            $object->{(string) $metadata->setter}($value);

            return;
        }

        if (false === $metadata->writeInClassScope) {
            $object->{(string) $metadata->setter} = $value;

            return;
        }

        $writer = $this->writers[$metadata->declaringClass][$metadata->property] ?? $this->createWriter($metadata);

        $writer($object, $value);
    }

    /**
     * @throws SerializerException
     */
    public function isInitialized(object $object, Metadata $metadata): bool
    {
        [$declaringClass, $property] = $this->resolveProperty($metadata);

        return new ReflectionProperty($declaringClass, $property)
            ->isInitialized($object);
    }

    /**
     * @throws SerializerException
     */
    private function createReader(Metadata $metadata): Closure
    {
        [$declaringClass, $property] = $this->resolveProperty($metadata);

        return $this->readers[$declaringClass][$property] = $this->bindToClass(
            static fn (object $object): mixed => $object->{$property},
            $declaringClass,
        );
    }

    /**
     * @throws SerializerException
     */
    private function createWriter(Metadata $metadata): Closure
    {
        [$declaringClass, $property] = $this->resolveProperty($metadata);

        return $this->writers[$declaringClass][$property] = $this->bindToClass(
            static function (object $object, mixed $value) use ($property): void {
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
