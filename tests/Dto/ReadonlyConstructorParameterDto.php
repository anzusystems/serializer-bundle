<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;

final class ReadonlyConstructorParameterDto
{
    #[Serialize]
    public readonly int $limit;

    public function __construct(int $limit = 10)
    {
        $this->limit = $limit;
    }
}
