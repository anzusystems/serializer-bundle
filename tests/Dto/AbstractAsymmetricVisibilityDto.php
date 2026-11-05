<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;

abstract class AbstractAsymmetricVisibilityDto
{
    #[Serialize]
    public protected(set) int $count = 0;
}
