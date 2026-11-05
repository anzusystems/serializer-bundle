<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;

final class PrivateHookedDto extends AbstractHookedDto
{
    #[Serialize]
    private string $name = '' {
        set => trim($value);
    }

    public static function create(string $name, int $count): self
    {
        $dto = new self();
        $dto->name = $name;
        $dto->count = $count;

        return $dto;
    }
}
