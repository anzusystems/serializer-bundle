<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\TestApp\Entity;

use AnzuSystems\SerializerBundle\Attributes\Serialize;
use AnzuSystems\SerializerBundle\Handler\Handlers\ArrayStringHandler;
use AnzuSystems\SerializerBundle\Tests\TestApp\Model\ExampleBackedEnum;
use AnzuSystems\SerializerBundle\Tests\TestApp\Model\ExampleUnitEnum;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\NilUuid;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity]
#[ORM\Table('example')]
class Example
{
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[Serialize]
    private int $id = 0;

    #[ORM\Column(type: UuidType::NAME)]
    #[Serialize]
    private Uuid $uuid;

    #[ORM\Column(type: Types::STRING)]
    #[Serialize]
    private string $name = '';

    #[ORM\Column(name: 'created_at', type: Types::DATETIME_IMMUTABLE)]
    #[Serialize]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(enumType: ExampleBackedEnum::class)]
    #[Serialize]
    private ExampleBackedEnum $place = ExampleBackedEnum::First;

    #[Serialize]
    private ExampleUnitEnum $color = ExampleUnitEnum::Red;

    /**
     * @var array<int|string>
     */
    #[ORM\Column(type: Types::JSON)]
    #[Serialize(handler: ArrayStringHandler::class)]
    private array $letters = [];

    public function __construct()
    {
        $this
            ->setUuid(new NilUuid())
            ->setCreatedAt(new DateTimeImmutable())
        ;
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

    public function getUuid(): Uuid
    {
        return $this->uuid;
    }

    public function setUuid(Uuid $uuid): self
    {
        $this->uuid = $uuid;

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

    /**
     * @return array<int|string>
     */
    public function getLetters(): array
    {
        return $this->letters;
    }

    /**
     * @param array<int|string> $letters
     */
    public function setLetters(array $letters): self
    {
        $this->letters = $letters;

        return $this;
    }
}
