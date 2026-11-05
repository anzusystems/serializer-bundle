<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;

abstract class AbstractPrivateDto
{
    #[Serialize]
    private string $direct = '';

    #[Serialize]
    private string $accessed = '';

    #[Serialize]
    private string $shadowed = 'parent';

    #[Serialize]
    private string $hidden = 'parent';

    public function getAccessed(): string
    {
        return $this->accessed;
    }

    public function setAccessed(string $accessed): void
    {
        $this->accessed = trim($accessed);
    }

    protected function fillPrivate(string $direct, string $accessed): void
    {
        $this->direct = $direct;
        $this->accessed = $accessed;
    }
}
