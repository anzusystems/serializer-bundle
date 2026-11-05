<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;

final class InheritedConstructorChildDto extends InheritedConstructorParentDto
{
    #[Serialize]
    public string $name = 'child';
}
