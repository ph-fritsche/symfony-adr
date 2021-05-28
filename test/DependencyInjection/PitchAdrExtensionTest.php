<?php
namespace Pitch\AdrBundle\DependencyInjection;

use Pitch\AdrBundle\EventSubscriber\GracefulSubscriber;
use Pitch\AdrBundle\EventSubscriber\ResponderSubscriber;
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
            $container->findDefinition(GracefulSubscriber::class)->getArgument('$globalGraceful')
        );
    }

    public function testInjectDefaultContentType()
    {
        $container = new ContainerBuilder();
        $container->setParameter('pitch_adr.defaultContentType', 'foo');

        $extension = new PitchAdrExtension();
        $extension->load([], $container);

        $this->assertEquals(
            'foo',
            $container->findDefinition(ResponderSubscriber::class)->getArgument('$defaultContentType')
        );
    }
}
