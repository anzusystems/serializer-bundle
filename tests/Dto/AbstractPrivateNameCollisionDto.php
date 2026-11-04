<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;

abstract class AbstractPrivateNameCollisionDto
{
    #[Serialize(serializedName: 'id')]
    private int $legacyId = 1;

    public function getLegacyId(): int
    {
        return $this->legacyId;
    }
}
