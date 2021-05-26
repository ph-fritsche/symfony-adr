<?php
namespace Pitch\AdrBundle\Responder\Handler;

use Pitch\AdrBundle\Responder\ResponseHandlerInterface;
use Pitch\AdrBundle\Responder\ResponsePayloadEvent;
use Symfony\Component\HttpFoundation\Response;

class ObjectHandler implements ResponseHandlerInterface
{
    public function getSupportedPayloadTypes(): array
    {
        return [
            'object',
        ];
    }

    public function handleResponsePayload(
        ResponsePayloadEvent $payloadEvent
    ): void {
        if ($payloadEvent->payload instanceof Response) {
            return;
        }

        $fullName = \is_object($payloadEvent->payload) ?
            \get_class($payloadEvent->payload) :
            \gettype($payloadEvent->payload);

        $p = \strrpos($fullName, '\\');
        $className = $p !== false ? \substr($fullName, $p +1) : $fullName;

        $payloadEvent->payload = [
            $className => $payloadEvent->payload,
        ];
    }
}
