<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;

final class SetterWithoutGetterDto
{
    #[Serialize]
    private string $name = '';

    public function setName(string $name): void
    {
        $this->name = trim($name);
    }
}
