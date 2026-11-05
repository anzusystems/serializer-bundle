<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\Dto;

use AnzuSystems\SerializerBundle\Attributes\Serialize;

final class PublicPropertyDto
{
    #[Serialize]
    public string $name = '';
}
