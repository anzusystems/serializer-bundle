<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;

final class ReadonlyNotPromotedDto
{
    #[Serialize]
    public private(set) readonly string $id;

    public function __construct()
    {
        $this->id = 'anzu';
    }
}
