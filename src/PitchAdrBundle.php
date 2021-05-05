<?php
namespace Pitch\AdrBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Pitch\AdrBundle\DependencyInjection\PitchAdrExtension;
use Pitch\AdrBundle\DependencyInjection\Compiler\ResponseHandlerPass;

/*
 * The class needs to be named exactly as the first part of the namespace.
 */
class PitchAdrBundle extends Bundle
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
