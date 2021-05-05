<?php
namespace Pitch\AdrBundle\DependencyInjection\Compiler;

use stdClass;
use LogicException;
use Pitch\AdrBundle\Responder\Responder;
use Symfony\Component\DependencyInjection\Reference;
use Pitch\AdrBundle\Responder\ResponsePayloadEvent;
use Symfony\Component\DependencyInjection\Definition;
use Pitch\AdrBundle\Responder\ResponseHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ResponseHandlerPassTest extends \PHPUnit\Framework\TestCase
{
    private ContainerBuilder $container;

    private Definition $responderDefinition;

    private ResponseHandlerPass $pass;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();

        $this->responderDefinition = new Definition(Responder::class);
        $this->container->setDefinition(Responder::class, $this->responderDefinition);

        $this->pass = new ResponseHandlerPass();
    }

    public function testMissingResponder()
    {
        $this->container->removeDefinition(Responder::class);

        $this->pass->process($this->container);

        $this->assertTrue(true);
    }

    public function testIgnoreAbstract()
    {
        $fooHandler = $this->addHandlerMockDefinition('fooId', ['payloadA', 'payloadB']);
        $fooHandler->addTag(ResponseHandlerPass::TAG);
        $fooHandler->setAbstract(true);

        $this->pass->process($this->container);

        $this->assertEquals([], $this->responderDefinition->getArgument('$handlerMap'));
    }

    public function testIgnoreUntaggedFactory()
    {
        $fooService = $this->addDefinition(null, 'foo');

        $this->pass->process($this->container);

        $this->assertEquals([], $this->responderDefinition->getArgument('$handlerMap'));
    }

    public function testIgnoreNotLoadableClass()
    {
        $this->addDefinition('FooClass', 'foo');

        try {
            $tempAutoload = function ($className) {
                throw new LogicException($className);
            };
            spl_autoload_register($tempAutoload, true, true);

            $this->pass->process($this->container);
        } finally {
            spl_autoload_unregister($tempAutoload);
        }

        $this->assertEquals([], $this->responderDefinition->getArgument('$handlerMap'));
    }

    public function testTaggedResponseHandler()
    {
        $fooHandler = $this->addHandlerMockDefinition('fooId', ['payloadA' => 456, 'payloadB']);
        $fooHandler->addTag(ResponseHandlerPass::TAG);

        $barHandler = $this->addHandlerMockDefinition('barId', ['payloadB']);
        $barHandler->addTag(ResponseHandlerPass::TAG, ['priority' => 123]);

        $bazHandler = $this->addHandlerMockDefinition('bazId', ['payloadB']);
        $bazHandler->addTag(ResponseHandlerPass::TAG, ['priority' => -123]);

        $this->pass->process($this->container);

        $this->assertEquals(
            [
                'payloadA' => [
                    ['fooId', 456],
                ],
                'payloadB' => [
                    ['barId', 123],
                    ['fooId', 0],
                    ['bazId', -123],
                ],
            ],
            $this->responderDefinition->getArgument('$handlerMap')
        );

        $this->assertEquals(
            [
                'fooId' => new Reference('fooId'),
                'barId' => new Reference('barId'),
                'bazId' => new Reference('bazId'),
            ],
            $this->responderDefinition->getArgument('$handlerObjects')
        );
    }

    public function testAutotaggedResponseHandler()
    {
        $this->addHandlerMockDefinition('fooId', ['payloadA' => 123]);

        $this->pass->process($this->container);

        $this->assertEquals(
            [
                'payloadA' => [
                    ['fooId', 123],
                ],
            ],
            $this->responderDefinition->getArgument('$handlerMap')
        );

        $this->assertEquals(
            [
                'fooId' => new Reference('fooId'),
            ],
            $this->responderDefinition->getArgument('$handlerObjects')
        );
    }

    public function testInvalidTypesTag()
    {
        $fooHandler = $this->addHandlerMockDefinition('fooId', ['payloadA']);
        $fooHandler->addTag(ResponseHandlerPass::TAG, ['for' => ['payloadB']]);

        $this->expectException(LogicException::class);

        $this->pass->process($this->container);
    }

    private function addHandlerMockDefinition(
        string $id = null,
        array $supportedTypes = [],
        bool $doesImplementInterface = true
    ): Definition {
        $mockCode = 'return new class()';
        if ($doesImplementInterface) {
            $mockCode .= ' implements ' . ResponseHandlerInterface::class;
        }
        $mockCode .= '{';

        $mockCode .= 'private static $supportedTypes = ';
        $mockCode .= \var_export($supportedTypes, true);
        $mockCode .= ';';

        $mockCode .= 'public function getSupportedPayloadTypes(): array { return $this::$supportedTypes; }';

        $mockCode .= 'public function handleResponsePayload(' . ResponsePayloadEvent::class . ' $event) {}';

        $mockCode .= '};';

        $mockObject = eval($mockCode);

        $a = $mockObject->getSupportedPayloadTypes();

        $mockClass = \get_class($mockObject);
        $b = (new $mockClass())->getSupportedPayloadTypes();

        $c = ($a != $b);

        return $this->addDefinition(\get_class($mockObject), $id);
    }

    private function addDefinition(
        ?string $class = null,
        ?string $id = null
    ): Definition {
        $definition = new Definition($class);

        $this->container->setDefinition($id ?? $class, $definition);

        return $definition;
    }
}
