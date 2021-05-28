<?php
namespace Pitch\AdrBundle\Responder;

use stdClass;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;

class ResponderTest extends \PHPUnit\Framework\TestCase
{
    public function testGetHandlerMap()
    {
        /** @var ContainerInterface */
        $container = $this->createMock(ContainerInterface::class);

        $map = ['foo', 'bar', 'baz'];

        $responder = new Responder($container, $map, []);

        $this->assertEquals($map, $responder->getHandlerMap());
    }

    public function provideHandlePayload()
    {
        return [
            'scalar' => [
                'stringPayload',
                [
                    'int' => [
                        'badfoo',
                    ],
                    'string' => [
                        ['foo', -123],
                        'bar',
                        ['name' => 'baz'],
                    ],
                ],
                ['foo', 'bar', 'baz'],
            ],
            'object' => [
                new stdClass(),
                [
                    'int' => [
                        'badfoo',
                    ],
                    stdClass::class => [
                        ['foo', -123],
                        'bar',
                        ['name' => 'baz'],
                    ],
                ],
                ['foo', 'bar', 'baz'],
            ],
            'object parents' => [
                $payload = new class() extends stdClass {
                },
                [
                    'int' => [
                        'badfoo',
                    ],
                    stdClass::class => [
                        'foo',
                        ['name' => 'baz'],
                    ],
                    \get_class($payload) => [
                        'bar',
                    ],
                ],
                ['bar', 'foo', 'baz'],
            ],
            'change payload' => [
                'stringPayload',
                [
                    'int' => [
                        'foo',
                    ],
                    'string' => [
                        'bar',
                        'baz',
                    ],
                ],
                [
                    ['bar', 'set' => 3],
                    ['foo', 'set' => 'newStringPayload'],
                    'bar',
                    'baz',
                ],
            ],
            'stop event' => [
                'stringPayload',
                [
                    'string' => [
                        'foo',
                        'bar',
                    ],
                ],
                [
                    ['foo', 'stop' => true],
                ],
            ],
            'get handler from container' => [
                'stringPayload',
                [
                    'string' => [
                        'foo',
                        'bar',
                    ],
                ],
                ['foo', 'bar'],
                ['foo', 'bar'],
            ],
            'circular handler' => [
                'foo',
                [
                    'int' => [
                        'foo',
                    ],
                    'string' => [
                        'bar',
                    ],
                ],
                [
                    ['bar', 'set' => 3],
                    ['foo', 'set' => 'foo'],
                ],
                [],
                CircularHandlerException::class,
            ],
            'prioritised handlers' => [
                'stringPayload',
                [
                    'string' => [
                        'A',
                        'pA',
                        'pB',
                        'pC',
                        'B',
                        'pD',
                        'pE',
                        'C',
                    ],
                    'int' => [
                        'pA',
                        'pB',
                        'pC',
                    ],
                ],
                [
                    ['A'],
                    ['pB'],
                    ['pA'],
                    ['pC'],
                    ['B'],
                    ['pE', 'set' => 1],
                    ['pC'],
                    ['pA'],
                ],
                [
                    'C',
                ],
                null,
                [
                    'pA' => 0,
                    'pB' => [10, null],
                    'pC' => [0, 1],
                    'pD' => .5,
                    'pE' => 1,
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideHandlePayload
     */
    public function testHandlePayload(
        $payload,
        $handlerMap,
        $expectedHandlers,
        $expectedContainerGet = [],
        $expectedException = null,
        $handlerPriorities = []
    ) {
        $event = $this->getResponsePayloadEvent($payload);
        $responder = $this->getResponder($handlerMap, $expectedHandlers, $expectedContainerGet, $handlerPriorities);

        if (isset($expectedException)) {
            $this->expectException($expectedException);
        }

        $responder->handleResponsePayload($event);
    }

    private function getResponder($handlerMap, $expectedHandlers, $expectedContainerGet, $handlerPriorities): Responder
    {
        $handlerObjects = [];
        foreach (\array_keys($handlerPriorities) as $id) {
            $handlerObjects[$id] = [];
        }
        foreach ($expectedContainerGet as $id) {
            $handlerObjects[$id] = [];
        }
        foreach ($expectedHandlers as &$description) {
            if (\is_string($description)) {
                $description = [$description];
            }
            $id = $description[0];
            $handlerObjects[$id][] = $description;
        }

        $positionAssert = new HandlerPositionsAssert($expectedHandlers);

        foreach ($handlerObjects as $id => $descriptions) {
            $handlerObjects[$id] = isset($handlerPriorities[$id])
                ? new TestPrioritisedResponseHandler($id, $positionAssert, $descriptions, $handlerPriorities[$id])
                : new TestResponseHandler($id, $positionAssert, $descriptions);
        }

        $expectedContainerGetObjects = [];
        foreach ($expectedContainerGet as $i => $id) {
            $expectedContainerGetObjects[$i] = $handlerObjects[$id];
            unset($handlerObjects[$id]);
        }

        $containerMock = $this->createMock(ContainerInterface::class);
        $getMethod = $containerMock->expects($this->exactly(\count($expectedContainerGet)))->method('get');
        $getMethod->withConsecutive(...\array_map(
            fn($i) => [$this->equalTo($i)],
            $expectedContainerGet
        ));
        if (\count($expectedContainerGetObjects)) {
            $getMethod->willReturn(...$expectedContainerGetObjects);
        }

        return new Responder($containerMock, $handlerMap, $handlerObjects);
    }

    private function getResponsePayloadEvent(
        $payload
    ): ResponsePayloadEvent {
        $event = new ResponsePayloadEvent($payload, new Request());
        $event->payload = $payload;

        return $event;
    }
}
