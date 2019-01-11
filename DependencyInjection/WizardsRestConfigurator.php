<?php

namespace Wizards\RestBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\Reference;
use WizardsRest\ObjectManager\DoctrineOrmObjectManager;
use WizardsRest\ObjectManager\ArrayObjectManager;
use WizardsRest\ObjectReader\ArrayReader;
use WizardsRest\ObjectReader\DoctrineAnnotationReader;
use WizardsRest\Paginator\ArrayPagerfantaPaginator;
use WizardsRest\Paginator\DoctrineOrmPagerFantaPaginator;

/**
 * A Helper to configure services according to the configuration file.
 */
class WizardsRestConfigurator
{
    public function getPaginatorClass(array $config): string
    {
        if (isset($config['data_source']) && 'orm' === $config['data_source']) {
            return DoctrineOrmPagerFantaPaginator::class;
        }

        return ArrayPagerfantaPaginator::class;
    }

    public function getReaderClass(array $config): string
    {
        if ('annotation' === $config['reader']) {
            return DoctrineAnnotationReader::class;
        }

        return ArrayReader::class;
    }

    public function getReaderArguments(array $config): array
    {
        if ('annotation' === $config['reader']) {
            return [new Reference('annotation_reader')];
        }

        return [];
    }

    public function getObjectManagerClass(array $config): string
    {
        if (isset($config['data_source']) && 'orm' === $config['data_source']) {
            return DoctrineOrmObjectManager::class;
        }

        return ArrayObjectManager::class;
    }

    public function getObjectManagerArguments(array $config): array
    {
        if ('annotation' === $config['reader']) {
            return [new Reference('doctrine.orm.entity_manager')];
        }

        return [];
    }
}
