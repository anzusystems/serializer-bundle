<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;

abstract class AbstractPrivatePromotedDto
{
    public function __construct(
        #[Serialize]
        private readonly string $name,
    ) {
    }
}
