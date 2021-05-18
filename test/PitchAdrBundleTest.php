<?php
namespace Pitch\AdrBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Pitch\AdrBundle\DependencyInjection\Compiler\ResponseHandlerPass;
use Pitch\AdrBundle\DependencyInjection\PitchAdrExtension;

class PitchAdrBundleTest extends \PHPUnit\Framework\TestCase
{
    public function testAddCompilerPass()
    {
        $container = $this->createMock(ContainerBuilder::class);

        $container->expects($this->once())->method('addCompilerPass')
            ->with($this->isInstanceOf(ResponseHandlerPass::class));

        $bundle = new PitchAdrBundle();

        /** @var ContainerBuilder $container */
        $bundle->build($container);
    }

    public function testGetContainerExtension()
    {
        $bundle = new PitchAdrBundle();

        $this->assertInstanceOf(PitchAdrExtension::class, $bundle->getContainerExtension());
    }
}
