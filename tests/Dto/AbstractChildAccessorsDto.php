<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;

abstract class AbstractChildAccessorsDto
{
    #[Serialize]
    protected string $name = '';

    #[Serialize]
    protected string $readOnly = 'parent';
}
