<?php
namespace Pitch\AdrBundle\Resources\config;

use Pitch\AdrBundle\Command\ResponderDebugCommand;
use Pitch\AdrBundle\Util\ClassFinder;
use Pitch\AdrBundle\Util\ClassFinderFactory;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container) {
    $container->parameters()
        ->set('pitch_adr.classfinder.cache', '%kernel.cache_dir%/loadable_classes.php');

    $container->services()
        ->defaults()
            ->autowire()
        ->set(ResponderDebugCommand::class)
            ->tag('console.command')
        ->set(ClassFinder::class)
            ->factory(service(ClassFinderFactory::class))
        ->set(ClassFinderFactory::class)
    ;
};
