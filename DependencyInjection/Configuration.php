<?php

namespace Andevis\Bundle\UploaderBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('andevis_uploader');

        $rootNode
        	->children()
        		->scalarNode('store_mode')
        			->info('Store mode for uploaded files. Available values: database, file, both')
        			->defaultValue('database')
        			->cannotBeEmpty()
        			->validate()
        				->ifNotInArray(array('database', 'file', 'both'))->thenInvalid('Available values: database, file, both')
        			->end()
        		->end()

        	->end();


        return $treeBuilder;
    }
}
