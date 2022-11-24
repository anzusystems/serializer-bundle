<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Service;

use AnzuSystems\SerializerBundle\Attributes\Serialize;
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
    public function serialize(object|iterable $data): string
    {
        $dataArray = $this->toArray($data);

        try {
            return json_encode($dataArray, JSON_THROW_ON_ERROR);
        } catch (JsonException $jsonException) {
            throw new SerializerException('Cannot encode json data.', 0, $jsonException);
        }
    }

    /**
     * @throws SerializerException
     */
    public function toArray(object|iterable $data, ?Metadata $metadata = null): array|object
    {
        if (is_iterable($data)) {
            $output = [];
            foreach ($data as $key => $item) {
                $output[$key] = (is_scalar($item) || null === $item) ? $item : $this->toArray($item, $metadata);
            }
            if (Serialize::KEYS_VALUES === $metadata?->strategy) {
                if (empty($output)) {
                    return new \stdClass();
                }
                return $output;
            }

            return array_values($output);
        }

        return $this->objectToArray($data);
    }

    /**
     * @throws SerializerException
     */
    private function objectToArray(object $data): array
    {
        $output = [];
        foreach ($this->metadataRegistry->get($data::class) as $name => $metadata) {
            $value = $data->{$metadata->getter}();
            $output[$name] = $this->handlerResolver
                ->getSerializationHandler($value, $metadata->customHandler)
                ->serialize($value, $metadata)
            ;
        }

        return $output;
    }
}
