<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;
use DateTimeImmutable;

final class ReadonlySetByConstructorDateDto
{
    #[Serialize]
    public readonly DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable('2026-01-01T00:00:00Z');
    }
}
