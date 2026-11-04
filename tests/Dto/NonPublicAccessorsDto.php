<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;

final class NonPublicAccessorsDto
{
    #[Serialize]
    private string $name = '';

    public static function create(string $name): self
    {
        $dto = new self();
        $dto->name = $name;

        return $dto;
    }

    private function getName(): string
    {
        return 'never read: ' . $this->name;
    }

    private function setName(string $name): void
    {
        $this->name = 'never written: ' . $name;
    }
}
