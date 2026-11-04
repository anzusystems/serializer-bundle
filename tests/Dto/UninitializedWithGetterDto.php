<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;

final class UninitializedWithGetterDto
{
    #[Serialize]
    private string $accessed;

    public function getAccessed(): string
    {
        return $this->accessed;
    }
}
