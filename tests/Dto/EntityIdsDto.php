<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;
use AnzuSystems\SerializerBundle\Handler\Handlers\EntityIdHandler;
use AnzuSystems\SerializerBundle\Tests\TestApp\Entity\Example;

final class EntityIdsDto
{
    /**
     * @var list<Example>
     */
    #[Serialize(handler: EntityIdHandler::class, type: Example::class)]
    private array $examples = [];

    /**
     * @return list<Example>
     */
    public function getExamples(): array
    {
        return $this->examples;
    }

    /**
     * @param list<Example> $examples
     */
    public function setExamples(array $examples): self
    {
        $this->examples = $examples;

        return $this;
    }
}
