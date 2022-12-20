<?php

declare(strict_types=1);

namespace AnzuSystems\SerializerBundle\DependencyInjection;

use AnzuSystems\SerializerBundle\AnzuSystemsSerializerBundle;
use AnzuSystems\SerializerBundle\Handler\HandlerResolver;
use AnzuSystems\SerializerBundle\Handler\Handlers\ArrayStringHandler;
use AnzuSystems\SerializerBundle\Handler\Handlers\BasicHandler;
use AnzuSystems\SerializerBundle\Handler\Handlers\DateTimeHandler;
use AnzuSystems\SerializerBundle\Handler\Handlers\EntityIdHandler;
use AnzuSystems\SerializerBundle\Handler\Handlers\EnumHandler;
use AnzuSystems\SerializerBundle\Handler\Handlers\HandlerInterface;
use AnzuSystems\SerializerBundle\Handler\Handlers\ObjectHandler;
use AnzuSystems\SerializerBundle\Metadata\MetadataFactory;
use AnzuSystems\SerializerBundle\Metadata\MetadataRegistry;
use AnzuSystems\SerializerBundle\OpenApi\SerializerModelDescriber;
use AnzuSystems\SerializerBundle\Request\ParamConverter\SerializerParamConverter;
use AnzuSystems\SerializerBundle\Request\ValueResolver\SerializerValueResolver;
use AnzuSystems\SerializerBundle\Serializer;
use AnzuSystems\SerializerBundle\Service\JsonDeserializer;
use AnzuSystems\SerializerBundle\Service\JsonSerializer;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Nelmio\ApiDocBundle\ModelDescriber\ModelDescriberInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;

final class AnzuSystemsSerializerExtension extends Extension
{
    /**
     * @throws Exception
     */
    public function load(array $configs, ContainerBuilder $container): void
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $this->loadSerializer($container, $config);
    }

    private function loadSerializer(ContainerBuilder $container, array $config): void
    {
        $container->setDefinition(
            BasicHandler::class,
            (new Definition(BasicHandler::class))
                ->addTag(AnzuSystemsSerializerBundle::TAG_SERIALIZER_HANDLER)
        );
        $container->setDefinition(
            DateTimeHandler::class,
            (new Definition(DateTimeHandler::class))
                ->addTag(AnzuSystemsSerializerBundle::TAG_SERIALIZER_HANDLER)
                ->setArgument('$serializerDateFormat', $config[Configuration::CONFIG_DATE_FORMAT])
        );
        $container->setDefinition(
            EnumHandler::class,
            (new Definition(EnumHandler::class))
                ->addTag(AnzuSystemsSerializerBundle::TAG_SERIALIZER_HANDLER)
        );
        $container->setDefinition(
            ObjectHandler::class,
            (new Definition(ObjectHandler::class))
                ->addTag(AnzuSystemsSerializerBundle::TAG_SERIALIZER_HANDLER)
                ->setArgument('$jsonSerializer', new Reference(JsonSerializer::class))
                ->setArgument('$jsonDeserializer', new Reference(JsonDeserializer::class))
        );
        $container->setDefinition(
            ArrayStringHandler::class,
            (new Definition(ArrayStringHandler::class))
                ->addTag(AnzuSystemsSerializerBundle::TAG_SERIALIZER_HANDLER)
        );
        if (interface_exists(EntityManagerInterface::class)) {
            $container->setDefinition(
                EntityIdHandler::class,
                (new Definition(EntityIdHandler::class))
                    ->addTag(AnzuSystemsSerializerBundle::TAG_SERIALIZER_HANDLER)
                    ->setArgument('$entityManager', new Reference(EntityManagerInterface::class))
            );
        }
        $container
            ->registerForAutoconfiguration(HandlerInterface::class)
            ->addTag(AnzuSystemsSerializerBundle::TAG_SERIALIZER_HANDLER)
        ;

        $container->setDefinition(
            HandlerResolver::class,
            new Definition(HandlerResolver::class)
        );

        $container->setDefinition(
            MetadataFactory::class,
            new Definition(MetadataFactory::class)
        );

        $container->setDefinition(
            MetadataRegistry::class,
            (new Definition(MetadataRegistry::class))
                ->setArgument('$appCache', new Reference(CacheItemPoolInterface::class))
                ->setArgument('$appLogger', new Reference(LoggerInterface::class))
                ->setArgument('$metadataFactory', new Reference(MetadataFactory::class))
        );

        $container->setDefinition(
            JsonSerializer::class,
            (new Definition(JsonSerializer::class))
                ->setArgument('$handlerResolver', new Reference(HandlerResolver::class))
                ->setArgument('$metadataRegistry', new Reference(MetadataRegistry::class))
        );

        $container->setDefinition(
            JsonDeserializer::class,
            (new Definition(JsonDeserializer::class))
                ->setArgument('$handlerResolver', new Reference(HandlerResolver::class))
                ->setArgument('$metadataRegistry', new Reference(MetadataRegistry::class))
        );

        $container->setDefinition(
            Serializer::class,
            (new Definition(Serializer::class))
                ->setArgument('$jsonSerializer', new Reference(JsonSerializer::class))
                ->setArgument('$jsonDeserializer', new Reference(JsonDeserializer::class))
        );

        if (interface_exists(ParamConverterInterface::class)) {
            $container->setDefinition(
                SerializerParamConverter::class,
                (new Definition(SerializerParamConverter::class))
                    ->setArgument('$serializer', new Reference(Serializer::class))
                    ->addTag('request.param_converter', [
                        'priority' => false,
                        'converter' => SerializerParamConverter::class,
                    ])
            );
        }

        if (interface_exists(ValueResolverInterface::class)) {
            $container
                ->register(SerializerValueResolver::class)
                ->setArgument('$serializer', new Reference(Serializer::class))
                ->addTag('controller.argument_value_resolver', ['priority' => 150]);
        }

        if (interface_exists(ModelDescriberInterface::class)) {
            $container->setDefinition(
                SerializerModelDescriber::class,
                (new Definition(SerializerModelDescriber::class))
                    ->setArgument('$metadataRegistry', new Reference(MetadataRegistry::class))
                    ->setArgument('$handlerResolver', new Reference(HandlerResolver::class))
                    ->setArgument('$annotationsReader', new Reference('annotations.reader'))
                    ->addTag('nelmio_api_doc.model_describer', ['priority' => 500])
            );
        }
    }
}
