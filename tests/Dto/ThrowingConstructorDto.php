<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;
use InvalidArgumentException;

final class ThrowingConstructorDto
{
    public function __construct(
        #[Serialize]
        public string $name,
    ) {
        throw new InvalidArgumentException('Rejected ' . $this->name);
    }
}
