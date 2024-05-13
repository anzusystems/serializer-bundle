<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;
use DateTimeImmutable;

final class FooDto
{
    #[Serialize]
    private int $bar;

    #[Serialize]
    private string $baz;

    #[Serialize(serializedName: 'bar-dto')]
    private BarDto $barDto;

    #[Serialize]
    private DateTimeImmutable $corge;

    #[Serialize]
    private bool $garply;

    #[Serialize]
    private ?string $fred = null;

    // Should be ignored by serializer
    private string $waldo = 'default-waldo-data';

    public function __construct(int $bar, string $baz, BarDto $barDto)
    {
        $this->bar = $bar;
        $this->baz = $baz;
        $this->barDto = $barDto;
    }

    public function getBar(): int
    {
        return $this->bar;
    }

    public function setBar(int $bar): self
    {
        $this->bar = $bar;

        return $this;
    }

    public function getBaz(): string
    {
        return $this->baz;
    }

    public function setBaz(string $baz): self
    {
        $this->baz = $baz;

        return $this;
    }

    public function getBarDto(): BarDto
    {
        return $this->barDto;
    }

    public function setBarDto(BarDto $barDto): self
    {
        $this->barDto = $barDto;

        return $this;
    }

    public function getCorge(): DateTimeImmutable
    {
        return $this->corge;
    }

    public function setCorge(DateTimeImmutable $corge): self
    {
        $this->corge = $corge;

        return $this;
    }

    public function isGarply(): bool
    {
        return $this->garply;
    }

    public function setGarply(bool $garply): self
    {
        $this->garply = $garply;

        return $this;
    }

    public function getFred(): ?string
    {
        return $this->fred;
    }

    public function setFred(?string $fred): self
    {
        $this->fred = $fred;

        return $this;
    }

    public function getWaldo(): string
    {
        return $this->waldo;
    }

    public function setWaldo(string $waldo): self
    {
        $this->waldo = $waldo;

        return $this;
    }
}
