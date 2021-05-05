<?php
namespace Pitch\AdrBundle\DependencyInjection;

use Pitch\AdrBundle\EventSubscriber\ControllerSubscriber;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class PitchAdrExtensionTest extends \PHPUnit\Framework\TestCase
{
    public function testAlias()
    {
        $extension = new PitchAdrExtension();

        $this->assertEquals(PitchAdrExtension::ALIAS, $extension->getAlias());
    }

    public function testInjectGraceful()
    {
        $config = [
            'graceful' => [
                ['value' => 'Foo', 'not' => []],
            ],
        ];

        $extension = new PitchAdrExtension();
        $container = new ContainerBuilder();

        $extension->load([$config], $container);

        $this->assertEquals(
            $config['graceful'],
            $container->findDefinition(ControllerSubscriber::class)->getArgument('$globalGraceful')
        );
    }
}
