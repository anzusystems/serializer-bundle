<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;

final class VirtualSetOnlyDto
{
    #[Serialize]
    public private(set) string $name = '';

    #[Serialize]
    public string $rawName {
        set(string $value) {
            $this->name = trim($value);
        }
    }

    public static function create(string $name): self
    {
        $dto = new self();
        $dto->name = $name;

        return $dto;
    }
}
