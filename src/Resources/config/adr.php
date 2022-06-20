<?php
namespace Pitch\AdrBundle\Resources\config;

use Pitch\AdrBundle\EventSubscriber\GracefulSubscriber;
use Pitch\AdrBundle\EventSubscriber\ResponderSubscriber;
use Pitch\AdrBundle\Responder\Responder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->defaults()
            ->autowire()
        ->set(GracefulSubscriber::class)
            ->tag('kernel.event_subscriber')
        ->set(ResponderSubscriber::class)
            ->tag('kernel.event_subscriber')
        ->set(Responder::class)
            ->alias(ContainerInterface::class, 'service_container')
    ;
};
