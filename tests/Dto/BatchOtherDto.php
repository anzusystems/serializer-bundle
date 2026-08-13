<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;
use AnzuSystems\SerializerBundle\Tests\TestApp\Serializer\RecordingBatchHandler;

final class BatchOtherDto
{
    #[Serialize(handler: RecordingBatchHandler::class)]
    private string $ref;

    public function __construct(string $ref)
    {
        $this->ref = $ref;
    }

    public function getRef(): string
    {
        return $this->ref;
    }
}
