<?php
namespace Pitch\AdrBundle\Responder;

interface ResponseHandlerInterface
{
    public function getSupportedPayloadTypes(): array;

    public function handleResponsePayload(
        ResponsePayloadEvent $payloadEvent
    );
}
