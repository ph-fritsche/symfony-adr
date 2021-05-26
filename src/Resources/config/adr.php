<?php
namespace Pitch\AdrBundle\Resources\config;

use Pitch\AdrBundle\EventSubscriber\ControllerSubscriber;
use Pitch\AdrBundle\EventSubscriber\ViewSubscriber;
use Pitch\AdrBundle\Responder\Responder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->defaults()
            ->autowire()
        ->set(ControllerSubscriber::class)
            ->tag('kernel.event_subscriber')
        ->set(ViewSubscriber::class)
            ->tag('kernel.event_subscriber')
        ->set(Responder::class)
    ;
};
