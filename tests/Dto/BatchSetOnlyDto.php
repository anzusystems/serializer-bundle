<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;
use AnzuSystems\SerializerBundle\Tests\TestApp\Serializer\RecordingBatchHandler;

final class BatchSetOnlyDto
{
    #[Serialize]
    public private(set) string $label;

    #[Serialize(handler: RecordingBatchHandler::class)]
    public string $code {
        set(string $value) {
            $this->label = $value;
        }
    }

    public function __construct(string $label)
    {
        $this->label = $label;
    }
}
