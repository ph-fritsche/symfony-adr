<?php
namespace nextdev\AdrBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use nextdev\AdrBundle\EventSubscriber\AdrSubscriber;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use nextdev\AdrBundle\DependencyInjection\Compiler\ResponseHandlerPass;

class NextdevAdrBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ResponseHandlerPass());
    }
}
