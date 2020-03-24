<?php
namespace nextdev\AdrBundle\EventSubscriber;

use nextdev\AdrBundle\Action\ActionProxy;
use nextdev\AdrBundle\Configuration\Graceful;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;

class ControllerSubscriber implements EventSubscriberInterface
{
    private array $globalGraceful;

    public function __construct(
        ?array $globalGraceful
    ) {
        $this->globalGraceful = \array_map(fn($g) => new Graceful($g), (array) $globalGraceful);
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::CONTROLLER_ARGUMENTS => ['onKernelControllerArguments', -1024],
        ];
    }

    public function onKernelControllerArguments(ControllerArgumentsEvent $event)
    {
        $graceful = (array) $event->getRequest()->attributes->get('_' . Graceful::ALIAS_NAME);

        if (\count($this->globalGraceful) === 0 && \count($graceful) === 0) {
            return;
        }

        $actionProxy = new ActionProxy($event->getController());
        $actionProxy->graceful = \array_merge($this->globalGraceful, $graceful);

        $event->setController($actionProxy);
    }
}
