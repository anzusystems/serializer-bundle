<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;

final class MethodNamesDto
{
    #[Serialize]
    public function size(): int
    {
        return 1;
    }

    #[Serialize]
    public function title(): string
    {
        return 'title';
    }

    #[Serialize]
    public function issue(): int
    {
        return 2;
    }

    #[Serialize]
    public function getCount(): int
    {
        return 3;
    }

    #[Serialize]
    public function isActive(): bool
    {
        return true;
    }

    #[Serialize]
    public function getter(): int
    {
        return 4;
    }
}
