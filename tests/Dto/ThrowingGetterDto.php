<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;
use LogicException;

final class ThrowingGetterDto
{
    #[Serialize]
    private string $name = '';

    public function getName(): string
    {
        throw new LogicException('Rejected ' . $this->name);
    }
}
