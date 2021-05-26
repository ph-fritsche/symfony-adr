<?php
namespace Pitch\AdrBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Pitch\AdrBundle\DependencyInjection\Compiler\ResponseHandlerPass;

class PitchAdrBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ResponseHandlerPass());
    }
}
