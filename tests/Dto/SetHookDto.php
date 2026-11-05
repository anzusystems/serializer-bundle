<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;

final class SetHookDto
{
    #[Serialize]
    public string $name = '' {
        set => trim($value);
    }
}
