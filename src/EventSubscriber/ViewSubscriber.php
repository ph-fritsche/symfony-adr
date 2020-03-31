<?php
namespace nextdev\AdrBundle\EventSubscriber;

use nextdev\AdrBundle\Responder\Responder;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use nextdev\AdrBundle\Responder\ResponsePayloadEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ViewSubscriber implements EventSubscriberInterface
{
    private Responder $responder;

    public function __construct(
        Responder $responder
    ) {
        $this->responder = $responder;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::VIEW => ['onKernelView', 1024],
        ];
    }

    public function onKernelView(ViewEvent $event)
    {
        $payloadEvent = new ResponsePayloadEvent();
        $payloadEvent->payload = $event->getControllerResult();
        $payloadEvent->request = $event->getRequest();

        $result = $this->responder->handleResponsePayload($payloadEvent);

        if ($result instanceof Response) {
            $event->setResponse($result);
        } else {
            $event->setControllerResult($result);
        }
    }
}
