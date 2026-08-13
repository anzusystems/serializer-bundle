<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\TestApp\Serializer;

use AnzuSystems\SerializerBundle\Context\SerializationContext;
use AnzuSystems\SerializerBundle\Handler\BatchItem;
use AnzuSystems\SerializerBundle\Handler\Handlers\AbstractHandler;
use AnzuSystems\SerializerBundle\Handler\Handlers\BatchSerializeHandlerInterface;
use AnzuSystems\SerializerBundle\Metadata\Metadata;

final class RecordingBatchHandler extends AbstractHandler implements BatchSerializeHandlerInterface
{
    /**
     * @var list<list<mixed>>
     */
    private array $batches = [];

    /**
     * @var list<list<string>>
     */
    private array $batchedProperties = [];

    /**
     * @var list<bool>
     */
    private array $batchedSerializeNulls = [];

    public function serialize(mixed $value, Metadata $metadata, SerializationContext $context): mixed
    {
        return $value;
    }

    public function deserialize(mixed $value, Metadata $metadata): mixed
    {
        return $value;
    }

    public function prepareSerializeBatch(SerializationContext $context, BatchItem ...$items): void
    {
        $this->batches[] = array_map(static fn (BatchItem $item): mixed => $item->value, $items);
        $this->batchedProperties[] = array_map(
            static fn (BatchItem $item): string => (string) $item->metadata->property,
            $items,
        );
        $this->batchedSerializeNulls[] = $context->shouldSerializeNull();
    }

    /**
     * @return list<list<mixed>>
     */
    public function getBatches(): array
    {
        return $this->batches;
    }

    /**
     * @return list<list<string>>
     */
    public function getBatchedProperties(): array
    {
        return $this->batchedProperties;
    }

    /**
     * @return list<bool>
     */
    public function getBatchedSerializeNulls(): array
    {
        return $this->batchedSerializeNulls;
    }
}
