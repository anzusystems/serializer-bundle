<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;

abstract class AbstractHookedDto
{
    #[Serialize]
    protected int $count = 0 {
        set => max(0, $value);
    }
}
