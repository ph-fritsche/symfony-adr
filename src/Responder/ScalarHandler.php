<?php
namespace Pitch\AdrBundle\Responder;

class ScalarHandler implements ResponseHandlerInterface
{
    public function getSupportedPayloadTypes(): array
    {
        return [
            'bool',
            'int',
            'float',
            'string',
        ];
    }

    public function handleResponsePayload(
        ResponsePayloadEvent $payloadEvent
    ): void {
        $payloadEvent->payload = [
            'value' => $payloadEvent->payload,
        ];
    }
}
