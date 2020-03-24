<?php
namespace nextdev\AdrBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use nextdev\AdrBundle\DependencyInjection\Compiler\ResponseHandlerPass;

class NextdevAdrBundleTest extends \PHPUnit\Framework\TestCase
{
    public function testAddCompilerPass()
    {
        $container = $this->createMock(ContainerBuilder::class);

        $container->expects($this->once())->method('addCompilerPass')
            ->with($this->isInstanceOf(ResponseHandlerPass::class));

        $bundle = new NextdevAdrBundle();

        $bundle->build($container);
    }
}
