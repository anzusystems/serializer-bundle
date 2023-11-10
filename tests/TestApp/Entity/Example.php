<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\TestApp\Entity;

use AnzuSystems\SerializerBundle\Attributes\Serialize;
use AnzuSystems\SerializerBundle\Tests\TestApp\Model\ExampleBackedEnum;
use AnzuSystems\SerializerBundle\Tests\TestApp\Model\ExampleUnitEnum;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table('example')]
class Example
{
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[Serialize]
    private int $id = 0;

    #[ORM\Column(type: Types::STRING)]
    #[Serialize]
    private string $name = '';

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    #[Serialize]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(enumType: ExampleBackedEnum::class)]
    #[Serialize]
    private ExampleBackedEnum $place = ExampleBackedEnum::First;

    #[ORM\Column(enumType: ExampleUnitEnum::class)]
    #[Serialize]
    private ExampleUnitEnum $color = ExampleUnitEnum::Red;

    public function __construct()
    {
        $this->setCreatedAt(new DateTimeImmutable());
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getPlace(): ExampleBackedEnum
    {
        return $this->place;
    }

    public function setPlace(ExampleBackedEnum $place): self
    {
        $this->place = $place;

        return $this;
    }

    public function getColor(): ExampleUnitEnum
    {
        return $this->color;
    }

    public function setColor(ExampleUnitEnum $color): self
    {
        $this->color = $color;

        return $this;
    }
}
