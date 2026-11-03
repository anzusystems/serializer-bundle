<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;

final class VirtualSetOnlyNestedDto
{
    private ?BazDto $baz = null;

    #[Serialize]
    public BazDto $rawBaz {
        set(BazDto $value) {
            $this->baz = $value;
        }
    }

    public function getBaz(): ?BazDto
    {
        return $this->baz;
    }
}
