<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;

class InheritedPromotedParentDto
{
    public function __construct(
        #[Serialize]
        public int $count = 10,
    ) {
    }
}
