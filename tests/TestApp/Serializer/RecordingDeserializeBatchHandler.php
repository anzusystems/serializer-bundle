<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\TestApp\Serializer;

use AnzuSystems\SerializerBundle\Context\SerializationContext;
use AnzuSystems\SerializerBundle\Handler\BatchItem;
use AnzuSystems\SerializerBundle\Handler\Handlers\AbstractHandler;
use AnzuSystems\SerializerBundle\Handler\Handlers\BatchDeserializeHandlerInterface;
use AnzuSystems\SerializerBundle\Metadata\Metadata;

final class RecordingDeserializeBatchHandler extends AbstractHandler implements BatchDeserializeHandlerInterface
{
    /**
     * @var list<list<mixed>>
     */
    private array $batches = [];

    public function serialize(mixed $value, Metadata $metadata, SerializationContext $context): mixed
    {
        return $value;
    }

    public function deserialize(mixed $value, Metadata $metadata): mixed
    {
        return $value;
    }

    public function prepareDeserializeBatch(BatchItem ...$items): void
    {
        $this->batches[] = array_map(static fn (BatchItem $item): mixed => $item->value, $items);
    }

    /**
     * @return list<list<mixed>>
     */
    public function getBatches(): array
    {
        return $this->batches;
    }
}
