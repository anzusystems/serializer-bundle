<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\TestApp\Model;

enum ExampleBackedEnum: string
{
    case First = 'first';
    case Second = 'second';
    case Third = 'third';
}
