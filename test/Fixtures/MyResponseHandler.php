<?php
namespace Pitch\AdrBundle\Fixtures;

use Pitch\AdrBundle\Responder\ResponseHandlerInterface;
use Pitch\AdrBundle\Responder\ResponsePayloadEvent;

class MyResponseHandler implements ResponseHandlerInterface
{
    public function getSupportedPayloadTypes(): array
    {
        return [
            MyResponseHandler::class,
        ];
    }

    public function handleResponsePayload(ResponsePayloadEvent $payloadEvent)
    {
        $payloadEvent->payload = 'foo';
    }
}
