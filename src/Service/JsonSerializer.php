<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Service;

use AnzuSystems\SerializerBundle\Attributes\Serialize;
use AnzuSystems\SerializerBundle\Context\SerializationContext;
use AnzuSystems\SerializerBundle\Exception\SerializerException;
use AnzuSystems\SerializerBundle\Handler\BatchItem;
use AnzuSystems\SerializerBundle\Handler\HandlerResolver;
use AnzuSystems\SerializerBundle\Handler\Handlers\BatchSerializeHandlerInterface;
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
            $preparedValues = $this->prepareBatches($data, $context);
            $output = [];
            foreach ($data as $key => $item) {
                if (null === $item) {
                    if ($context->shouldSerializeNull()) {
                        $output[$key] = null;
                    }

                    continue;
                }

                $output[$key] = match (true) {
                    is_scalar($item) => $item,
                    is_iterable($item) => $this->toArray($item, $metadata, $context),
                    default => $this->objectToArray($item, $context, $preparedValues[spl_object_id($item)] ?? []),
                };
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
     * @param array<string, mixed> $preparedValues
     *
     * @throws SerializerException
     */
    private function objectToArray(object $data, SerializationContext $context, array $preparedValues = []): array
    {
        $output = [];
        foreach ($this->metadataRegistry->get($data::class)->getAll() as $name => $metadata) {
            $value = array_key_exists($name, $preparedValues)
                ? $preparedValues[$name]
                : $this->getValue($data, $metadata);

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
     * @return array<int, array<string, mixed>> the values it read, by object id and serialized name
     *
     * @throws SerializerException
     */
    private function prepareBatches(iterable $data, SerializationContext $context): array
    {
        // A generator would be consumed by this pass, so only arrays and collections are prepared.
        $traversableTwice = is_array($data) || $data instanceof Collection;
        if (false === $traversableTwice) {
            return [];
        }

        $batches = [];
        $preparedValues = [];
        foreach ($data as $item) {
            if (false === is_object($item)) {
                continue;
            }

            foreach ($this->metadataRegistry->get($item::class)->getAll() as $name => $metadata) {
                $handlerClass = $metadata->customHandler;
                if (null === $handlerClass || false === is_a($handlerClass, BatchSerializeHandlerInterface::class, true)) {
                    continue;
                }

                $value = $this->getValue($item, $metadata);
                $preparedValues[spl_object_id($item)][$name] = $value;
                $batches[$handlerClass][] = new BatchItem($value, $metadata);
            }
        }

        foreach ($batches as $handlerClass => $items) {
            $handler = $this->handlerResolver->getHandler($handlerClass);
            if ($handler instanceof BatchSerializeHandlerInterface) {
                $handler->prepareSerializeBatch($context, ...$items);
            }
        }

        return $preparedValues;
    }

    private function getValue(object $data, Metadata $metadata): mixed
    {
        return $metadata->getterSetterStrategy ? $data->{$metadata->getter}() : $data->{$metadata->property};
    }
}
