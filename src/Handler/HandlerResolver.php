<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Handler;

use AnzuSystems\SerializerBundle\Exception\SerializerException;
use AnzuSystems\SerializerBundle\Handler\Handlers\HandlerInterface;
use AnzuSystems\SerializerBundle\Metadata\Metadata;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

final class HandlerResolver
{
    public function __construct(
        private readonly ContainerInterface $handlerLocator,
        private readonly array $handlers,
    ) {
    }

    /**
     * @throws SerializerException
     */
    public function getSerializationHandler(mixed $value, ?string $customHandler): HandlerInterface
    {
        try {
            if ($customHandler) {
                return $this->handlerLocator->get($customHandler);
            }
            /** @var class-string<HandlerInterface> $handler */
            foreach ($this->handlers as $handler) {
                if ($handler::supportsSerialize($value)) {
                    return $this->handlerLocator->get($handler);
                }
            }
        } catch (NotFoundExceptionInterface|ContainerExceptionInterface $exception) {
            throw new SerializerException('Unable to get handler.', 0, $exception);
        }

        throw new SerializerException('Unable to determine serialization handler');
    }

    /**
     * @throws SerializerException
     */
    public function getDeserializationHandler(
        mixed $value,
        string $type,
        ?string $customHandler
    ): HandlerInterface {
        try {
            if ($customHandler) {
                return $this->handlerLocator->get($customHandler);
            }
            /** @var class-string<HandlerInterface> $handler */
            foreach ($this->handlers as $handler) {
                if ($handler::supportsDeserialize($value, $type)) {
                    return $this->handlerLocator->get($handler);
                }
            }
        } catch (NotFoundExceptionInterface|ContainerExceptionInterface $exception) {
            throw new SerializerException('Unable to get handler.', 0, $exception);
        }

        throw new SerializerException('Unable to determine deserialization handler');
    }

    /**
     * @throws SerializerException
     */
    public function getDescriptionHandler(string $property, Metadata $metadata): HandlerInterface
    {
        try {
            if ($metadata->customHandler) {
                return $this->handlerLocator->get($metadata->customHandler);
            }

            /** @var class-string<HandlerInterface> $handler */
            foreach ($this->handlers as $handler) {
                if ($handler::supportsDescribe($property, $metadata)) {
                    return $this->handlerLocator->get($handler);
                }
            }
        } catch (NotFoundExceptionInterface|ContainerExceptionInterface $exception) {
            throw new SerializerException('Unable to get handler.', 0, $exception);
        }

        throw new SerializerException('Unable to determine description handler');
    }
}
