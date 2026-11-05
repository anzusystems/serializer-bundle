<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;

final class PromotedSetterDto
{
    public function __construct(
        #[Serialize]
        private int $count = 10,
    ) {
    }

    public function getCount(): int
    {
        return $this->count;
    }

    public function setCount(int $count): void
    {
        $this->count = abs($count);
    }
}
