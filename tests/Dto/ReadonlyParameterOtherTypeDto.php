<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;
use DateTimeImmutable;

final class ReadonlyParameterOtherTypeDto
{
    #[Serialize]
    public readonly DateTimeImmutable $at;

    public function __construct(string $at)
    {
        $this->at = new DateTimeImmutable($at);
    }
}
