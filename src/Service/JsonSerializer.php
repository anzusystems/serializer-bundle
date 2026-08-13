<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Service;

use AnzuSystems\SerializerBundle\Attributes\Serialize;
use AnzuSystems\SerializerBundle\Context\SerializationContext;
use AnzuSystems\SerializerBundle\Exception\SerializerException;
use AnzuSystems\SerializerBundle\Handler\HandlerResolver;
use AnzuSystems\SerializerBundle\Metadata\Metadata;
use AnzuSystems\SerializerBundle\Metadata\MetadataRegistry;
use Doctrine\Common\Collections\Collection;
use JsonException;

final class JsonSerializer
{
    public function __construct(
        private readonly HandlerResolver $handlerResolver,
        private readonly MetadataRegistry $metadataRegistry,
    ) {
    }

    /**
     * @throws SerializerException
     */
    public function serialize(object|iterable $data, SerializationContext $context): string
    {
        $dataArray = $this->toArray($data, null, $context);

        try {
            return json_encode($dataArray, JSON_THROW_ON_ERROR);
        } catch (JsonException $jsonException) {
            throw new SerializerException('Cannot encode json data.', 0, $jsonException);
        }
    }

    /**
     * @throws SerializerException
     */
    public function toArray(object|iterable $data, ?Metadata $metadata = null, ?SerializationContext $context = null): array|object
    {
        if (null === $context) {
            $context = SerializationContext::create();
        }

        if (is_iterable($data)) {
            $this->prepareBatches($data);
            $output = [];
            foreach ($data as $key => $item) {
                if (null === $item) {
                    if ($context->shouldSerializeNull()) {
                        $output[$key] = null;
                    }

                    continue;
                }

                $output[$key] = is_scalar($item) ? $item : $this->toArray($item, $metadata, $context);
            }

            if (Serialize::KEYS_VALUES === $metadata?->strategy) {
                if (empty($output)) {
                    return new \stdClass();
                }

                return $output;
            }

            return array_values($output);
        }

        return $this->objectToArray($data, $context);
    }

    /**
     * @throws SerializerException
     */
    private function objectToArray(object $data, SerializationContext $context): array
    {
        $output = [];
        foreach ($this->metadataRegistry->get($data::class)->getAll() as $name => $metadata) {
            $value = $this->getValue($data, $metadata);

            if (null === $value && !$context->shouldSerializeNull()) {
                continue;
            }

            $output[$name] = $this->handlerResolver
                ->getSerializationHandler($value, $metadata->customHandler)
                ->serialize($value, $metadata, $context)
            ;
        }

        return $output;
    }

    /**
     * @throws SerializerException
     */
    private function prepareBatches(iterable $data): void
    {
        // A generator would be consumed by this pass, so only arrays and collections are prepared.
        $traversableTwice = is_array($data) || $data instanceof Collection;
        if (false === $traversableTwice || false === $this->handlerResolver->hasBatchHandlers()) {
            return;
        }

        $handlers = [];
        $values = [];
        foreach ($data as $item) {
            if (false === is_object($item)) {
                continue;
            }

            foreach ($this->metadataRegistry->get($item::class)->getAll() as $metadata) {
                $handlerClass = $metadata->customHandler;
                if (null === $handlerClass) {
                    continue;
                }

                $handler = $this->handlerResolver->getBatchHandler($handlerClass);
                if (null === $handler) {
                    continue;
                }

                $handlers[$handlerClass] = $handler;
                $values[$handlerClass][] = $this->getValue($item, $metadata);
            }
        }

        foreach ($values as $handlerClass => $handlerValues) {
            $handlers[$handlerClass]->prepareSerializeBatch($handlerValues);
        }
    }

    private function getValue(object $data, Metadata $metadata): mixed
    {
        return $metadata->getterSetterStrategy ? $data->{$metadata->getter}() : $data->{$metadata->property};
    }
}
