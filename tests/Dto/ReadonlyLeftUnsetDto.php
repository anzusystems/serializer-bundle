<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;

final class ReadonlyLeftUnsetDto
{
    #[Serialize]
    public readonly string $id;

    public function __construct()
    {
    }

    public static function create(string $id): self
    {
        $dto = new self();
        $dto->id = $id;

        return $dto;
    }
}
