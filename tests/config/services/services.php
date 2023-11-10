<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use AnzuSystems\SerializerBundle\Tests\TestApp\Controller\DummyController;

return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();

    $services->set(DummyController::class)
        ->autowire(true)
        ->autoconfigure(true)
    ;
};
