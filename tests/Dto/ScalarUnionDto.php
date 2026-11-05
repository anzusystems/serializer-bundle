<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;

final class ScalarUnionDto
{
    #[Serialize]
    public int|string $value = 0;

    #[Serialize]
    public int|float|null $number = null;
}
