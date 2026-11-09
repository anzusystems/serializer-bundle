<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\Tests\TestApp\Proxy;

use AnzuSystems\SerializerBundle\Tests\TestApp\Entity\Example;
use Doctrine\Persistence\Proxy;

final class ExampleProxy extends Example implements Proxy
{
    public function __load(): void
    {
    }

    public function __isInitialized(): bool
    {
        return true;
    }
}
