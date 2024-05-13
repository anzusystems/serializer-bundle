<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;

final class BazDto
{
    #[Serialize]
    private int $qux;
    private string $quux;

    public function __construct(int $qux, string $quux)
    {
        $this->qux = $qux;
        $this->quux = $quux;
    }

    public function getQux(): int
    {
        return $this->qux;
    }

    public function getQuux(): string
    {
        return $this->quux;
    }
}
