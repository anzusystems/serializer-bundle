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

    public function serialize(mixed $value, Metadata $metadata, SerializationContext $context): mixed
    {
        return $value;
    }

    public function deserialize(mixed $value, Metadata $metadata): mixed
    {
        return $value;
    }

    public function prepareSerializeBatch(array $values): void
    {
        $this->batches[] = $values;
    }

    /**
     * @return list<list<mixed>>
     */
    public function getBatches(): array
    {
        return $this->batches;
    }
}
