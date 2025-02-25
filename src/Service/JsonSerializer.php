<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Service;

use AnzuSystems\SerializerBundle\Attributes\Serialize;
use AnzuSystems\SerializerBundle\Context\SerializationContext;
use AnzuSystems\SerializerBundle\Exception\SerializerException;
use AnzuSystems\SerializerBundle\Handler\HandlerResolver;
use AnzuSystems\SerializerBundle\Metadata\Metadata;
use AnzuSystems\SerializerBundle\Metadata\MetadataRegistry;
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
            $value = $metadata->getterSetterStrategy ? $data->{$metadata->getter}() : $data->{$metadata->property};

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
}
