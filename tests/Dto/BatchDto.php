<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;
use AnzuSystems\SerializerBundle\Tests\TestApp\Serializer\RecordingBatchHandler;

final class BatchDto
{
    #[Serialize(handler: RecordingBatchHandler::class)]
    private string $code;

    #[Serialize]
    private string $label;
    private int $codeReads = 0;

    public function __construct(string $code, string $label)
    {
        $this->code = $code;
        $this->label = $label;
    }

    public function getCode(): string
    {
        $this->codeReads++;

        return $this->code;
    }

    public function getCodeReads(): int
    {
        return $this->codeReads;
    }

    public function getLabel(): string
    {
        return $this->label;
    }
}
