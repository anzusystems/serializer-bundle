<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;

final class RedeclaredPromotedDto extends RedeclaredPromotedParentDto
{
    #[Serialize]
    protected int $count;

    public function getCount(): int
    {
        return $this->count;
    }
}
