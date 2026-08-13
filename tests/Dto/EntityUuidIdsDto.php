<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;
use AnzuSystems\SerializerBundle\Handler\Handlers\EntityIdHandler;
use AnzuSystems\SerializerBundle\Tests\TestApp\Entity\ExampleUuid;

final class EntityUuidIdsDto
{
    /**
     * @var list<ExampleUuid>
     */
    #[Serialize(handler: EntityIdHandler::class, type: ExampleUuid::class)]
    private array $examples = [];

    /**
     * @return list<ExampleUuid>
     */
    public function getExamples(): array
    {
        return $this->examples;
    }

    /**
     * @param list<ExampleUuid> $examples
     */
    public function setExamples(array $examples): self
    {
        $this->examples = $examples;

        return $this;
    }
}
