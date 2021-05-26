<?php
namespace Pitch\AdrBundle\Responder\Handler;

use Pitch\AdrBundle\Responder\ResponseHandlerInterface;
use Pitch\AdrBundle\Responder\ResponsePayloadEvent;

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
