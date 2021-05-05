<?php
namespace Pitch\AdrBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Pitch\AdrBundle\EventSubscriber\AdrSubscriber;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Pitch\AdrBundle\DependencyInjection\PitchAdrExtension;
use Pitch\AdrBundle\DependencyInjection\Compiler\ResponseHandlerPass;

/*
 * phpcs:ignoreFile Squiz.Classes.ValidClassName.NotCamelCaps
 * The class needs to be named exactly as the first part of the namespace.
 */

class pitchAdrBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ResponseHandlerPass());
    }

    protected function getContainerExtensionClass(): string
    {
        return PitchAdrExtension::class;
    }
}
