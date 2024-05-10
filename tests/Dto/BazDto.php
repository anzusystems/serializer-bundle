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

    public function setQux(int $qux): self
    {
        $this->qux = $qux;

        return $this;
    }

    public function getQuux(): string
    {
        return $this->quux;
    }

    public function setQuux(string $quux): self
    {
        $this->quux = $quux;

        return $this;
    }
}
