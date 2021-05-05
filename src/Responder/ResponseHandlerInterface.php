<?php
namespace Pitch\AdrBundle\Responder;

use Symfony\Component\HttpFoundation\Request;

interface ResponseHandlerInterface
{
    public function getSupportedPayloadTypes(): array;

    public function handleResponsePayload(
        ResponsePayloadEvent $payloadEvent
    );
}
