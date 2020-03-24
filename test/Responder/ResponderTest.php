<?php
namespace nextdev\AdrBundle\Responder;

use stdClass;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ResponderTest extends \PHPUnit\Framework\TestCase
{
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
        ];
    }

    /**
     * @dataProvider provideHandlePayload
     */
    public function testHandlePayload(
        $payload,
        $handlerMap,
        $expectedHandlers,
        $expectedContainerGet = []
    ) {
        $event = $this->getResponsePayloadEvent($payload);
        $responder = $this->getResponder($handlerMap, $expectedHandlers, $expectedContainerGet);

        $responder->handleResponsePayload($event);
    }

    private function getResponder($handlerMap, $expectedHandlers, $expectedContainerGet): Responder
    {
        $handlerObjects = [];
        $expectedPosition = 0;
        $relay = function ($method, ...$arguments) {
            return $this->$method(...$arguments);
        };
        $assertPosition = function ($expectedPosition) {
            static $actualPosition = 0;
            $this->assertEquals($expectedPosition, $actualPosition++);
        };
    

        foreach ($expectedHandlers as $description) {
            if (\is_string($description)) {
                $description = [$description];
            }
            $handlerObjects[$description[0]][$expectedPosition++] = $description;
        }

        $handlerObjects = \array_map(
            fn($descriptions) => new class(
            $assertPosition,
            $relay,
            $descriptions
            ) implements ResponseHandlerInterface
            {
                public function __construct($assertPosition, $relay, $descriptions)
                {
                    $this->assertPosition = $assertPosition;
                    $this->relay = $relay;
                    $this->expectedPositions = \array_keys($descriptions);
                    $this->descriptions = \array_values($descriptions);
                    $this->callCount = 0;
                }

                public function getSupportedPayloadTypes(): array
                {
                    return [];
                }

                public function handleResponsePayload(
                    ResponsePayloadEvent $event
                ) {
                    $call = $this->callCount++;

                    ($this->assertPosition)($this->expectedPositions[$call] ?? false);

                    if (\array_key_exists('set', $this->descriptions[$call])) {
                        $event->payload = $this->descriptions[$call]['set'];
                    }

                    if (\array_key_exists('stop', $this->descriptions[$call])) {
                        $event->stopPropagation = (bool) $this->descriptions[$call]['stop'];
                    }
                }
            },
            $handlerObjects
        );

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
        $event = new ResponsePayloadEvent();
        $event->payload = $payload;

        return $event;
    }
}
