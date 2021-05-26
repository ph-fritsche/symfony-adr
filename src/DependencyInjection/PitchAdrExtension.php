<?php
namespace Pitch\AdrBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Pitch\AdrBundle\EventSubscriber\ControllerSubscriber;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class PitchAdrExtension extends Extension
{
    const ALIAS = 'pitch_adr';

    public function getAlias(): string
    {
        return static::ALIAS;
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $loader->load('adr.php');
        $loader->load('debug.php');
        $loader->load('handler.php');

        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $container->findDefinition(ControllerSubscriber::class)->setArgument('$globalGraceful', $config['graceful']);
    }
}