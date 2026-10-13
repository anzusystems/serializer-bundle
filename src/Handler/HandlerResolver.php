<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Handler;

use AnzuSystems\SerializerBundle\Exception\SerializerException;
use AnzuSystems\SerializerBundle\Handler\Handlers\HandlerInterface;
use AnzuSystems\SerializerBundle\Metadata\Metadata;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Handlers are remembered per value type (and target type), so supportsSerialize() and supportsDeserialize() must
 * decide by type only.
 */
final class HandlerResolver
{
    /**
     * @var array<string, HandlerInterface>
     */
    private array $handlerInstances = [];

    /**
     * @var array<string, class-string<HandlerInterface>>
     */
    private array $serializationHandlers = [];

    /**
     * @var array<string, array<string, class-string<HandlerInterface>>>
     */
    private array $deserializationHandlers = [];

    /**
     * @param list<class-string<HandlerInterface>> $handlers
     * @param array<class-string<HandlerInterface>, true> $nonSharedHandlers
     */
    public function __construct(
        private readonly ContainerInterface $handlerLocator,
        private readonly array $handlers,
        private readonly array $nonSharedHandlers = [],
    ) {
    }

    /**
     * @throws SerializerException
     */
    public function getHandler(string $handlerClass): HandlerInterface
    {
        if (isset($this->nonSharedHandlers[$handlerClass])) {
            return $this->fetchHandler($handlerClass);
        }

        return $this->handlerInstances[$handlerClass] ??= $this->fetchHandler($handlerClass);
    }

    /**
     * @throws SerializerException
     */
    public function getSerializationHandler(mixed $value, ?string $customHandler): HandlerInterface
    {
        if (false === empty($customHandler)) {
            return $this->getHandler($customHandler);
        }

        return $this->getHandler($this->serializationHandlers[self::getValueType($value)] ??= $this->findHandler(
            static fn (string $handler): bool => $handler::supportsSerialize($value),
            'serialization',
        ));
    }

    /**
     * @throws SerializerException
     */
    public function getDeserializationHandler(
        mixed $value,
        string $type,
        ?string $customHandler
    ): HandlerInterface {
        if (false === empty($customHandler)) {
            return $this->getHandler($customHandler);
        }

        return $this->getHandler($this->deserializationHandlers[self::getValueType($value)][$type] ??= $this->findHandler(
            static fn (string $handler): bool => $handler::supportsDeserialize($value, $type),
            'deserialization',
        ));
    }

    /**
     * @throws SerializerException
     */
    public function getDescriptionHandler(string $property, Metadata $metadata): HandlerInterface
    {
        if (null !== $metadata->customHandler && '' !== $metadata->customHandler) {
            return $this->getHandler($metadata->customHandler);
        }

        return $this->getHandler($this->findHandler(
            static fn (string $handler): bool => $handler::supportsDescribe($property, $metadata),
            'description',
        ));
    }

    /**
     * get_debug_type() names an anonymous class only by its first parent or interface, so two such classes would share
     * one remembered handler.
     */
    private static function getValueType(mixed $value): string
    {
        return is_object($value) ? $value::class : get_debug_type($value);
    }

    /**
     * @param callable(class-string<HandlerInterface>): bool $supports
     *
     * @return class-string<HandlerInterface>
     *
     * @throws SerializerException
     */
    private function findHandler(callable $supports, string $purpose): string
    {
        foreach ($this->handlers as $handler) {
            if ($supports($handler)) {
                return $handler;
            }
        }

        throw new SerializerException(sprintf('Unable to determine %s handler', $purpose));
    }

    /**
     * @throws SerializerException
     */
    private function fetchHandler(string $handlerClass): HandlerInterface
    {
        try {
            return $this->handlerLocator->get($handlerClass);
        } catch (NotFoundExceptionInterface|ContainerExceptionInterface $exception) {
            throw new SerializerException('Unable to get handler.', 0, $exception);
        }
    }
}
