<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;

final class WiderSetHookDto
{
    #[Serialize]
    public array $tags = [] {
        set(string|array $value) => is_string($value) ? explode(',', $value) : $value;
    }

    #[Serialize]
    public string $name = 'default' {
        set(?string $value) => $value ?? 'from null';
    }
}
