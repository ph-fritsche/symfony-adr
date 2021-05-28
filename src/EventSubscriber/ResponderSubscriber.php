<?php
namespace Pitch\AdrBundle\EventSubscriber;

use Pitch\AdrBundle\Configuration\DefaultContentType;
use Pitch\AdrBundle\Responder\Responder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Pitch\AdrBundle\Responder\ResponsePayloadEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ResponderSubscriber implements EventSubscriberInterface
{
    private Responder $responder;
    private ?string $defaultContentType;

    public function __construct(
        Responder $responder,
        ?string $defaultContentType = null
    ) {
        $this->responder = $responder;
        $this->defaultContentType = $defaultContentType;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['onKernelView', 1024],
        ];
    }

    public function onKernelView(ViewEvent $event)
    {
        $request = $event->getRequest();
        if ($this->defaultContentType && !$request->attributes->has('_' . DefaultContentType::class)) {
            $request->attributes->set(
                '_' . DefaultContentType::class,
                new DefaultContentType($this->defaultContentType),
            );
        }

        $payloadEvent = new ResponsePayloadEvent(
            $event->getControllerResult(),
            $request,
        );

        $result = $this->responder->handleResponsePayload($payloadEvent);

        if ($result instanceof Response) {
            $event->setResponse($result);
        } else {
            $event->setControllerResult($result);
        }
    }
}
