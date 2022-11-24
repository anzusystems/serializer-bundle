<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle;

use AnzuSystems\SerializerBundle\DependencyInjection\AnzuSystemsSerializerExtension;
use AnzuSystems\SerializerBundle\DependencyInjection\CompilerPass\SerializerHandlerCompilerPass;
use AnzuSystems\SerializerBundle\DependencyInjection\Configuration;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

final class AnzuSystemsSerializerBundle extends AbstractBundle
{
    public const TAG_SERIALIZER_HANDLER = Configuration::ALIAS . '.handler';

    public function build(ContainerBuilder $container): void
    {
        $container->registerExtension(new AnzuSystemsSerializerExtension());
        $container->addCompilerPass(new SerializerHandlerCompilerPass());
    }
}
