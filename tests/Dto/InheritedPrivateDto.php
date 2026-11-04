<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;

final class InheritedPrivateDto extends AbstractPrivateDto
{
    #[Serialize]
    private string $shadowed = 'child';
    private string $hidden = 'child';

    public static function create(string $direct, string $accessed, string $shadowed): self
    {
        $dto = new self();
        $dto->fillPrivate($direct, $accessed);
        $dto->shadowed = $shadowed;

        return $dto;
    }
}
