<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

class RedeclaredPromotedParentDto
{
    public function __construct(
        protected int $count = 10,
    ) {
    }
}
