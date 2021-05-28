<?php
namespace Pitch\AdrBundle\EventSubscriber;

use Pitch\AdrBundle\Action\ActionProxy;
use Pitch\AdrBundle\Configuration\Graceful;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;

class GracefulSubscriberTest extends EventSubscriberTest
{
    public function provideGraceful(): array
    {
        return [
            [
                [
                    ['value' => 'foo'],
                    ['value' => 'bar', 'not' => ['baz']],
                ],
                [
                ]
            ],
            [
                [
                ],
                [
                    ['value' => 'foo2'],
                ],
            ],
            [
                [
                    ['value' => 'foo'],
                    ['value' => 'bar', 'not' => ['baz']],
                ],
                [
                    ['value' => 'foo2'],
                ]
            ],
            [
                [],
                [],
            ]
        ];
    }

    /**
     * @dataProvider provideGraceful
     */
    public function testSetActionProxy(
        $globalGraceful,
        $controllerGraceful
    ) {
        $event = $this->getControllerArgumentsEvent($this->getGracefulForArray($controllerGraceful));

        $this->getSubscriberObject($globalGraceful)->onKernelControllerArguments($event);

        $controller = $event->getController();

        $expectedGraceful = \array_merge(
            $this->getGracefulForArray($globalGraceful),
            $this->getGracefulForArray($controllerGraceful)
        );

        if (\count($expectedGraceful)) {
            $this->assertInstanceOf(ActionProxy::class, $controller);
            /** @var object $controller */
            $this->assertEquals($expectedGraceful, $controller->graceful);
        } else {
            $this->assertNotInstanceOf(ActionProxy::class, $controller);
        }
    }

    /**
     * @return Graceful[]
     */
    protected function getGracefulForArray(
        array $gracefulList
    ): array {
        return \array_map(fn($g) => new Graceful($g), $gracefulList);
    }

    /**
     * @param Graceful[] $controllerGraceful
     */
    protected function getControllerArgumentsEvent(
        array $controllerGraceful
    ): ControllerArgumentsEvent {
        $request = new Request();
        $request->attributes->set('_' . Graceful::class, $controllerGraceful);

        /** @var HTTPKernelInterface */
        $httpKernel = $this->createMock(HttpKernelInterface::class);

        return  new ControllerArgumentsEvent(
            $httpKernel,
            function () {
            },
            [],
            $request,
            HttpKernelInterface::MASTER_REQUEST
        );
    }

    protected function getSubscriberObject(
        array $globalGraceful = [],
        bool $reader = false
    ): GracefulSubscriber {
        return new GracefulSubscriber(
            $globalGraceful,
        );
    }
}
