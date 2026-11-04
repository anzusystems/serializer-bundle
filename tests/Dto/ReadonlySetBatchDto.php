<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;
use AnzuSystems\SerializerBundle\Tests\TestApp\Serializer\RecordingDeserializeBatchHandler;

final class ReadonlySetBatchDto
{
    #[Serialize(handler: RecordingDeserializeBatchHandler::class)]
    public readonly string $code;

    public function __construct()
    {
        $this->code = 'constructor';
    }
}
