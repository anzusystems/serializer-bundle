<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;

class PlainNonPublicDto
{
    #[Serialize]
    private string $name = '';

    #[Serialize]
    protected int $count = 0;

    public static function create(string $name, int $count): self
    {
        $dto = new self();
        $dto->name = $name;
        $dto->count = $count;

        return $dto;
    }
}
