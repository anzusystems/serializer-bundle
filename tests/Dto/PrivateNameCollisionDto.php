<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;

final class PrivateNameCollisionDto extends AbstractPrivateNameCollisionDto
{
    #[Serialize]
    public string $id = 'child';
}
