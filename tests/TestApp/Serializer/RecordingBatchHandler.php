<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\TestApp\Serializer;

use AnzuSystems\SerializerBundle\Context\SerializationContext;
use AnzuSystems\SerializerBundle\Handler\Handlers\AbstractHandler;
use AnzuSystems\SerializerBundle\Handler\Handlers\BatchHandlerInterface;
use AnzuSystems\SerializerBundle\Metadata\Metadata;

final class RecordingBatchHandler extends AbstractHandler implements BatchHandlerInterface
{
    /**
     * @var list<list<mixed>>
     */
    private array $batches = [];

    /**
     * @var list<string>
     */
    private array $batchedProperties = [];

    /**
     * @var list<bool>
     */
    private array $batchedNullStrategies = [];

    public function serialize(mixed $value, Metadata $metadata, SerializationContext $context): mixed
    {
        return $value;
    }

    public function deserialize(mixed $value, Metadata $metadata): mixed
    {
        return $value;
    }

    public function prepareSerializeBatch(array $values, Metadata $metadata, SerializationContext $context): void
    {
        $this->batches[] = $values;
        $this->batchedProperties[] = (string) $metadata->property;
        $this->batchedNullStrategies[] = $context->shouldSerializeNull();
    }

    /**
     * @return list<list<mixed>>
     */
    public function getBatches(): array
    {
        return $this->batches;
    }

    /**
     * @return list<string>
     */
    public function getBatchedProperties(): array
    {
        return $this->batchedProperties;
    }

    /**
     * @return list<bool>
     */
    public function getBatchedNullStrategies(): array
    {
        return $this->batchedNullStrategies;
    }
}
