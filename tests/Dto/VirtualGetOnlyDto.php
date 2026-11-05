<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;

final class VirtualGetOnlyDto
{
    #[Serialize]
    public string $name = '';

    #[Serialize]
    public string $label {
        get => strtoupper($this->name);
    }
}
