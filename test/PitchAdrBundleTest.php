<?php
namespace Pitch\AdrBundle;

use Pitch\AdrBundle\Action\ActionProxy;
use Pitch\AdrBundle\Fixtures\MyException;
use Pitch\AdrBundle\Fixtures\MyResponseHandler;
use Pitch\AdrBundle\PitchAdrBundle;
use Pitch\Annotation\PitchAnnotationBundle;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelEvents;

class PitchAdrBundleTest extends KernelTestCase
{
    protected EventDispatcher $dispatcher;

    protected static function getKernelClass()
    {
        return get_class(new class('', true) extends Kernel
        {
            public function getProjectDir()
            {
                return $this->dir ??= sys_get_temp_dir() . '/PitchForm-' . uniqid() . '/';
            }

            public function registerBundles(): iterable
            {
                return [
                    new FrameworkBundle(),
                    new PitchAnnotationBundle(),
                    new PitchAdrBundle(),
                ];
            }

            public function registerContainerConfiguration(LoaderInterface $loader)
            {
                $loader->load(function (ContainerBuilder $containerBuilder) {
                    $containerBuilder->setParameter('kernel.secret', 'secret');
                });

                $loader->load(function (ContainerBuilder $containerBuilder) {
                    $containerBuilder->setDefinition(
                        'myHandler',
                        new Definition(MyResponseHandler::class),
                    );
                });
            }
        });
    }

    protected function boot(): void
    {
        self::bootKernel();

        $this->dispatcher = self::$kernel->getContainer()->get('event_dispatcher');
    }

    protected function dispatchControllerArgumentsEvent(
        callable $controller
    ): ControllerArgumentsEvent {
        $request = new Request();
        $controllerEvent = new ControllerEvent(self::$kernel, $controller, $request, null);

        $this->dispatcher->dispatch($controllerEvent, KernelEvents::CONTROLLER);

        $event = new ControllerArgumentsEvent(self::$kernel, $controllerEvent->getController(), [], $request, null);

        $this->dispatcher->dispatch($event, KernelEvents::CONTROLLER_ARGUMENTS);

        return $event;
    }

    public function testGracefulExceptions()
    {
        $this->boot();
        $controller = new class {
            /**
             * @\Pitch\AdrBundle\Configuration\Graceful(MyException::class)
             */
            public function throwMyExceptionGraceful()
            {
                throw new MyException();
            }

            public function throwRuntimeException()
            {
                // this is graceful per default global graceful
                throw new RuntimeException();
            }

            public function throwMyException()
            {
                throw new MyException();
            }
        };

        $event = $this->dispatchControllerArgumentsEvent([$controller, 'throwMyExceptionGraceful']);

        $this->assertInstanceOf(ActionProxy::class, $event->getController());
        $this->assertInstanceOf(MyException::class, $event->getController()());

        $event = $this->dispatchControllerArgumentsEvent([$controller, 'throwRuntimeException']);

        $this->assertInstanceOf(ActionProxy::class, $event->getController());
        $this->assertInstanceOf(RuntimeException::class, $event->getController()());

        $event = $event = $this->dispatchControllerArgumentsEvent([$controller, 'throwMyException']);

        $this->assertInstanceOf(ActionProxy::class, $event->getController());

        $this->expectException(MyException::class);
        $event->getController()();
    }

    protected function dispatchViewEvent($payload, Request $request = null)
    {
        $request ??= new Request();

        $event = new ViewEvent(self::$kernel, $request, HttpKernelInterface::MASTER_REQUEST, $payload);

        $this->dispatcher->dispatch($event, KernelEvents::VIEW);

        return $event;
    }

    public function testResponder()
    {
        $this->boot();

        $event = $this->dispatchViewEvent('foo');

        $this->assertEquals(['value' => 'foo'], $event->getControllerResult());
    }
}
