<?php
namespace nextdev\AdrBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use nextdev\AdrBundle\Responder\Responder;
use nextdev\AdrBundle\Configuration\Graceful;
use Symfony\Component\DependencyInjection\Reference;
use nextdev\AdrBundle\EventSubscriber\ViewSubscriber;
use Symfony\Component\DependencyInjection\Definition;
use nextdev\AdrBundle\EventSubscriber\ControllerSubscriber;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class NextdevAdrExtension extends Extension
{
    const ALIAS = 'nextdev_adr';

    public function getAlias(): string
    {
        return static::ALIAS;
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        $container->findDefinition(ControllerSubscriber::class)->setArgument('$globalGraceful', $config['graceful']);
    }
}
