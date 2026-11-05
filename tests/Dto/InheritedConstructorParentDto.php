<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

class InheritedConstructorParentDto
{
    public function __construct(
        private string $name = 'parent',
    ) {
    }

    public function getParentName(): string
    {
        return $this->name;
    }
}
