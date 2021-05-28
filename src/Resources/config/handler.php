<?php
namespace Pitch\AdrBundle\Resources\config;

use Pitch\AdrBundle\DependencyInjection\Compiler\ResponseHandlerPass;
use Pitch\AdrBundle\Responder\Handler\ObjectHandler;
use Pitch\AdrBundle\Responder\Handler\ScalarHandler;
use Pitch\AdrBundle\Responder\Handler\JsonResponder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container) {
    $container->services()
        ->defaults()
            ->autowire()
        ->set(ScalarHandler::class)
            ->tag(ResponseHandlerPass::TAG, ['priority' => -1024])
        ->set(ObjectHandler::class)
            ->tag(ResponseHandlerPass::TAG, ['priority' => -1024])
        ->set(JsonResponder::class)
            ->tag(ResponseHandlerPass::TAG, ['priority' => -8192])
    ;
};
