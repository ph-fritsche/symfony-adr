<?php
namespace nextdev\AdrBundle\DependencyInjection;

use RuntimeException;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder(NextdevAdrExtension::ALIAS);
        
        $treeBuilder->getRootNode()->children()
            ->arrayNode('graceful')
                ->defaultValue([
                        ['value' => RuntimeException::class]
                    ])
                ->arrayPrototype()
                ->beforeNormalization()
                    ->ifString()->then(function ($v) {
                        return [ 'value' => $v ];
                    })
                ->end()
                ->children()
                    ->scalarNode('value')->end()
                    ->arrayNode('not')
                        ->beforeNormalization()
                            ->ifString()->then(function ($v) {
                                return [ $v ];
                            })
                        ->end()
                        ->scalarPrototype()->end()
                    ->end()
                ->end()
            ->end()
        ->end();

        return $treeBuilder;
    }
}
