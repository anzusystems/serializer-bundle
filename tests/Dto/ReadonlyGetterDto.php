<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;

final class ReadonlyGetterDto
{
    #[Serialize]
    private readonly string $id;

    public function __construct()
    {
        $this->id = 'constructor';
    }

    public function getId(): string
    {
        return $this->id;
    }
}
