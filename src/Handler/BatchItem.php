<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Handler;

use AnzuSystems\SerializerBundle\Metadata\Metadata;

final readonly class BatchItem
{
    public function __construct(
        public mixed $value,
        public Metadata $metadata,
    ) {
    }
}
