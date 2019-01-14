<?php

namespace Wizards\RestBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Wizards\RestBundle\Services\FormatOptions;
use WizardsRest\Provider;
use WizardsRest\Serializer;

/**
 * Injects the proper service definition according to the configuration.
 */
class WizardsRestExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        $configurator = new WizardsRestConfigurator();

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yml');

        // configure the paginator
        $paginatorDefinition = $container->getDefinition('wizards_rest.paginator');
        $paginatorDefinition->setClass($configurator->getPaginatorClass($config));

        // configure the serializer
        $serializerDefinition = $container->getDefinition(Serializer::class);
        $serializerDefinition->addArgument($config['base_url']);

        // configure the reader
        $readerDefinition = $container->getDefinition('wizards_rest.reader');
        $readerDefinition->setClass($configurator->getReaderClass($config));
        $readerDefinition->setArguments($configurator->getReaderArguments($config));

        // configure the provider
        $subscriberDefinition = $container->getDefinition(Provider::class);
        $subscriberDefinition->addArgument(new Reference('wizards_rest.reader'));

        // configure the format options
        $formatDefinition = $container->getDefinition(FormatOptions::class);
        $formatDefinition->addArgument($config['format']);

        // configure the object manager
        $managerDefinition = $container->getDefinition('wizards_rest.object_manager');
        $managerDefinition->setClass($configurator->getObjectManagerClass($config));
        $managerDefinition->setArguments($configurator->getObjectManagerArguments($config));
    }
}
