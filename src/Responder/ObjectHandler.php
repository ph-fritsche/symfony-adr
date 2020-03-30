<?php
namespace nextdev\AdrBundle\Responder;

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
