<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;

final class ScalarTypesDto
{
    #[Serialize]
    public int $int = -1;

    #[Serialize]
    public float $float = -1.0;

    #[Serialize]
    public string $string = 'default';

    #[Serialize]
    public bool $bool = true;
}
