<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;

final readonly class ReadonlyPromotedDto
{
    public function __construct(
        #[Serialize]
        public string $name,
        #[Serialize]
        public int $count,
    ) {
    }
}
