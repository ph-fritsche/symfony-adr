<?php
namespace nextdev\AdrBundle\DependencyInjection;

use nextdev\AdrBundle\EventSubscriber\ControllerSubscriber;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class NextdevAdrExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testAlias()
    {
        $extension = new NextdevAdrExtension();

        $this->assertEquals(NextdevAdrExtension::ALIAS, $extension->getAlias());
    }

    public function testInjectGraceful()
    {
        $config = [
            'graceful' => [
                ['value' => 'Foo', 'not' => []],
            ],
        ];

        $extension = new NextdevAdrExtension();
        $container = new ContainerBuilder();

        $extension->load([$config], $container);

        $this->assertEquals(
            $config['graceful'],
            $container->findDefinition(ControllerSubscriber::class)->getArgument('$globalGraceful')
        );
    }
}
