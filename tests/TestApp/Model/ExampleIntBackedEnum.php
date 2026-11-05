<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\TestApp\Model;

enum ExampleIntBackedEnum: int
{
    case One = 1;
    case Five = 5;
}
