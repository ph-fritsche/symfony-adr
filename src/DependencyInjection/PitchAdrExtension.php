<?php
namespace Pitch\AdrBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Pitch\AdrBundle\EventSubscriber\GracefulSubscriber;
use Pitch\AdrBundle\EventSubscriber\ResponderSubscriber;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class PitchAdrExtension extends Extension
{
    const ALIAS = 'pitch_adr';
    const PARAMETER_DEFAULT_CONTENT_TYPE = 'pitch_adr.defaultContentType';

    public function getAlias(): string
    {
        return static::ALIAS;
    }

    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));

        $loader->load('adr.php');
        $loader->load('debug.php');

        if ($config['defaultResponseHandlers']) {
            $loader->load('handler.php');
        }

        $container->findDefinition(GracefulSubscriber::class)->setArgument('$globalGraceful', $config['graceful']);

        $container->findDefinition(ResponderSubscriber::class)->setArgument(
            '$defaultContentType',
            $container->hasParameter(static::PARAMETER_DEFAULT_CONTENT_TYPE)
                ? $container->getParameter(static::PARAMETER_DEFAULT_CONTENT_TYPE)
                : null,
        );
    }
}
