<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use AnzuSystems\SerializerBundle\Tests\TestApp\Controller\DummyController;
use AnzuSystems\SerializerBundle\Tests\TestApp\Controller\ExampleController;

return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();

    $services->set(DummyController::class)
        ->autowire(true)
        ->autoconfigure(true)
    ;

    $services->set(ExampleController::class)
        ->autowire(true)
        ->autoconfigure(true)
    ;
};
