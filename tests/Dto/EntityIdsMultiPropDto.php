<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;
use AnzuSystems\SerializerBundle\Handler\Handlers\EntityIdHandler;
use AnzuSystems\SerializerBundle\Tests\TestApp\Entity\Example;

final class EntityIdsMultiPropDto
{
    /**
     * @var list<Example>
     */
    #[Serialize(handler: EntityIdHandler::class, type: Example::class)]
    private array $firstExamples = [];

    /**
     * @var list<Example>
     */
    #[Serialize(handler: EntityIdHandler::class, type: Example::class)]
    private array $secondExamples = [];

    #[Serialize(handler: EntityIdHandler::class, type: Example::class)]
    private ?Example $singleExample = null;

    /**
     * @return list<Example>
     */
    public function getFirstExamples(): array
    {
        return $this->firstExamples;
    }

    /**
     * @param list<Example> $firstExamples
     */
    public function setFirstExamples(array $firstExamples): self
    {
        $this->firstExamples = $firstExamples;

        return $this;
    }

    /**
     * @return list<Example>
     */
    public function getSecondExamples(): array
    {
        return $this->secondExamples;
    }

    /**
     * @param list<Example> $secondExamples
     */
    public function setSecondExamples(array $secondExamples): self
    {
        $this->secondExamples = $secondExamples;

        return $this;
    }

    public function getSingleExample(): ?Example
    {
        return $this->singleExample;
    }

    public function setSingleExample(?Example $singleExample): self
    {
        $this->singleExample = $singleExample;

        return $this;
    }
}
