<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;

final class StaticPropertyDto
{
    #[Serialize]
    private static int $counter = 5;

    public static function getCounter(): int
    {
        return self::$counter;
    }
}
