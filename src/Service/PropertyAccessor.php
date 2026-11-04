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

    /**
     * @throws SerializerException
     */
    public function setValue(object $object, Metadata $metadata, mixed $value): void
    {
        if ($metadata->setterIsMethod) {
            $object->{(string) $metadata->setter}($value);

            return;
        }

        [$declaringClass, $property] = $this->resolveProperty($metadata);
        $writer = $this->writers[$declaringClass . '::' . $property] ??= $this->bindToClass(
            static function (object $object, mixed $value) use ($property): void {
                $object->{$property} = $value;
            },
            $declaringClass,
        );

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
