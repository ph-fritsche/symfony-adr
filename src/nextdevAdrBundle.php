<?php
namespace nextdev\AdrBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use nextdev\AdrBundle\EventSubscriber\AdrSubscriber;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use nextdev\AdrBundle\DependencyInjection\NextdevAdrExtension;
use nextdev\AdrBundle\DependencyInjection\Compiler\ResponseHandlerPass;

/*
 * phpcs:ignoreFile Squiz.Classes.ValidClassName.NotCamelCaps
 * The class needs to be named exactly as the first part of the namespace.
 */

class nextdevAdrBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ResponseHandlerPass());
    }

    protected function getContainerExtensionClass(): string
    {
        return NextdevAdrExtension::class;
    }
}
