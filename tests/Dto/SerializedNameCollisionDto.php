<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;

final class SerializedNameCollisionDto
{
    public function __construct(
        public string $foo,
        #[Serialize(serializedName: 'foo')]
        private string $bar = 'default',
    ) {
    }
}
