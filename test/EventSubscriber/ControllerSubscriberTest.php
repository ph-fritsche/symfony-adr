<?php
namespace Pitch\AdrBundle\EventSubscriber;

use Doctrine\Common\Annotations\AnnotationReader;
use Pitch\AdrBundle\Action\ActionProxy;
use Pitch\AdrBundle\Configuration\Graceful;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class ControllerSubscriberTest extends EventSubscriberTest
{
    public function testReadAnnotations()
    {
        $event = $this->getControllerEvent(new class {
            /**
             * @Graceful("Foo")
             * @Graceful("Bar", not={"Baz"})
             */
            public function __invoke()
            {
            }
        });

        // without Doctrine Annotations
        $this->getSubscriberObject([], false)->onKernelController($event);
        $this->assertEquals([], $event->getRequest()->attributes->get('_' . Graceful::class));

        // with Doctrine Annotations
        $this->getSubscriberObject([], true)->onKernelController($event);
        $this->assertEquals([
            new Graceful(['value' => 'Foo']),
            new Graceful(['value' => 'Bar', 'not' => 'Baz']),
        ], $event->getRequest()->attributes->get('_' . Graceful::class));
    }

    public function testReadAttributes()
    {
        $event = $this->getControllerEvent(new class {
            #[Graceful(['value' => 'Foo'])]
            #[Graceful('Bar', not: 'Baz')]
            public function __invoke()
            {
            }
        });

        $this->getSubscriberObject([], false)->onKernelController($event);
        $this->assertEquals(
            PHP_MAJOR_VERSION >= 8
            ? [
                new Graceful(['value' => 'Foo']),
                new Graceful(['value' => 'Bar', 'not' => 'Baz']),
            ]
            : [],
            $event->getRequest()->attributes->get('_' . Graceful::class)
        );
    }

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
        $controllerSubscriber = $this->getSubscriberObject($globalGraceful);

        $event = $this->getControllerArgumentsEvent($this->getGracefulForArray($controllerGraceful));

        $controllerSubscriber->onKernelControllerArguments($event);

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

    protected function getControllerEvent(
        callable $controller
    ): ControllerEvent {
        return new ControllerEvent(
            $this->createMock(HttpKernelInterface::class),
            $controller,
            new Request(),
            HttpKernelInterface::MASTER_REQUEST,
        );
    }

    /**
     * @param Graceful[] $controllerGraceful
     */
    protected function getControllerArgumentsEvent(
        array $controllerGraceful
    ): ControllerArgumentsEvent {
        $request = new Request();
        $request->attributes->set('_' . Graceful::class, $controllerGraceful);

        return  new ControllerArgumentsEvent(
            $this->createMock(HttpKernelInterface::class),
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
    ): ControllerSubscriber {
        return new ControllerSubscriber(
            $reader ? new AnnotationReader() : null,
            $globalGraceful,
        );
    }
}
