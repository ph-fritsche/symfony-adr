<?php
namespace Pitch\AdrBundle\EventSubscriber;

use Doctrine\Common\Annotations\Reader;
use Pitch\AdrBundle\Action\ActionProxy;
use Pitch\AdrBundle\Configuration\Graceful;
use ReflectionAttribute;
use ReflectionMethod;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\Event\ControllerEvent;

class ControllerSubscriber implements EventSubscriberInterface
{
    private ?Reader $reader;
    private array $globalGraceful;

    public function __construct(
        ?Reader $reader,
        ?array $globalGraceful
    ) {
        $this->reader = $reader;
        $this->globalGraceful = \array_map(fn($g) => new Graceful($g), (array) $globalGraceful);
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER_ARGUMENTS => ['onKernelControllerArguments', -1024],
        ];
    }

    public function onKernelController(ControllerEvent $event)
    {
        $controller = $event->getController();

        if (\is_object($controller)) {
            $controller = [$controller, '__invoke'];
        }

        $reflMethod = new ReflectionMethod($controller[0], $controller[1]);

        $annotations = $this->reader
            ? $this->reader->getMethodAnnotations($reflMethod)
            : [];

        $attributes = PHP_MAJOR_VERSION >= 8
            ? \array_map(
                function (ReflectionAttribute $a) {
                    $class = $a->getName();
                    $args = $a->getArguments();
                    if (isset($args[0]) && \is_array($args[0])) {
                        return $a->newInstance();
                    }
                    return new $class(['value' => $args[0], 'not' => $args['not']]);
                },
                $reflMethod->getAttributes(Graceful::class, ReflectionAttribute::IS_INSTANCEOF),
            )
            : [];

        $event->getRequest()->attributes->set('_' . Graceful::class, [...$annotations, ...$attributes]);
    }

    public function onKernelControllerArguments(ControllerArgumentsEvent $event)
    {

        $graceful = (array) $event->getRequest()->attributes->get('_' . Graceful::class);

        if (\count($this->globalGraceful) === 0 && \count($graceful) === 0) {
            return;
        }

        $actionProxy = new ActionProxy($event->getController());
        $actionProxy->graceful = \array_merge($this->globalGraceful, $graceful);

        $event->setController($actionProxy);
    }
}
