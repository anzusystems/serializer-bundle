<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;
use DateTimeImmutable;

final class ReadonlyNullableParameterDto
{
    #[Serialize]
    public readonly DateTimeImmutable $at;

    #[Serialize]
    public readonly string $name;

    public function __construct(?DateTimeImmutable $at = null, ?string $name = null)
    {
        $this->at = $at ?? new DateTimeImmutable('2020-01-01T00:00:00Z');
        $this->name = $name ?? 'default';
    }
}
