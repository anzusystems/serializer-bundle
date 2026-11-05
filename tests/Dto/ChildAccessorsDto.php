<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

final class ChildAccessorsDto extends AbstractChildAccessorsDto
{
    public static function create(string $name): self
    {
        $dto = new self();
        $dto->name = $name;

        return $dto;
    }

    public function getName(): string
    {
        return strtoupper($this->name);
    }

    public function setName(string $name): void
    {
        $this->name = trim($name);
    }

    public function getReadOnly(): string
    {
        return $this->readOnly;
    }
}
