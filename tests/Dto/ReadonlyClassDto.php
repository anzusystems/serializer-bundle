<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;

final readonly class ReadonlyClassDto
{
    #[Serialize]
    public string $id;

    public static function create(string $id): self
    {
        $dto = new self();
        $dto->id = $id;

        return $dto;
    }
}
