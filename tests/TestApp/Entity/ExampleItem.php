<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\TestApp\Entity;

use AnzuSystems\SerializerBundle\Attributes\Serialize;
use AnzuSystems\SerializerBundle\Handler\Handlers\EntityIdHandler;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table('example_item')]
class ExampleItem
{
    #[ORM\Column(type: Types::INTEGER)]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[Serialize]
    private int $id = 0;

    #[ORM\Column(type: Types::STRING)]
    #[Serialize]
    private string $name = '';

    #[ORM\ManyToOne(targetEntity: Example::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    #[Serialize(handler: EntityIdHandler::class)]
    private Example $example;

    public function __construct()
    {
        $this
            ->setExample(new Example())
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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getExample(): Example
    {
        return $this->example;
    }

    public function setExample(Example $example): self
    {
        $this->example = $example;

        return $this;
    }
}
