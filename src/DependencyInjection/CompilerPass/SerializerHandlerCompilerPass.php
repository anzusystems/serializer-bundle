<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\DependencyInjection\CompilerPass;

use AnzuSystems\SerializerBundle\AnzuSystemsSerializerBundle;
use AnzuSystems\SerializerBundle\Handler\HandlerResolver;
use AnzuSystems\SerializerBundle\Handler\Handlers\HandlerInterface;
use Symfony\Component\DependencyInjection\Argument\ServiceLocatorArgument;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class SerializerHandlerCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (false === $container->hasDefinition(HandlerResolver::class)) {
            return;
        }

        $handlers = array_keys($container->findTaggedServiceIds(AnzuSystemsSerializerBundle::TAG_SERIALIZER_HANDLER));
        /**
         * @param class-string<HandlerInterface> $a
         * @param class-string<HandlerInterface> $b
         */
        $sortFn = fn (string $a, string $b): int => $b::getPriority() <=> $a::getPriority();
        usort($handlers, $sortFn);

        $handlerReferences = [];
        foreach ($handlers as $handler) {
            $handlerReferences[$handler] = new Reference($handler);
        }

        $handlerLocator = new ServiceLocatorArgument($handlerReferences);
        $container
            ->getDefinition(HandlerResolver::class)
            ->setArgument('$handlerLocator', $handlerLocator)
            ->setArgument('$handlers', $handlers)
        ;
    }
}
