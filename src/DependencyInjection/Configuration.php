<?php
namespace Pitch\AdrBundle\DependencyInjection;

use RuntimeException;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder(PitchAdrExtension::ALIAS);

        /** @var ArrayNodeDefinition */
        $root = $treeBuilder->getRootNode();
        $rootChildren = $root->children();

        $graceful = $rootChildren->arrayNode('graceful');
        $graceful->defaultValue([
            ['value' => RuntimeException::class]
        ]);
        $gracefulPrototype = $graceful->arrayPrototype();
        $gracefulPrototype->beforeNormalization()
            ->ifString()->then(function ($v) {
                return [ 'value' => $v ];
            });
        $gracefulChildren = $gracefulPrototype->children();
        $gracefulChildren->scalarNode('value');
        $gracefulNot = $gracefulChildren->arrayNode('not');
        $gracefulNot->beforeNormalization()
            ->ifString()->then(function ($v) {
                return [ $v ];
            });
        $gracefulNot->scalarPrototype();

        $rootChildren->booleanNode('defaultResponseHandlers')
            ->defaultValue(true);

        return $treeBuilder;
    }
}
