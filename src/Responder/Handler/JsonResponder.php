<?php
namespace Pitch\AdrBundle\Responder\Handler;

use JsonException;
use Pitch\AdrBundle\Responder\PrioritisedResponseHandlerInterface;
use Pitch\AdrBundle\Responder\ResponsePayloadEvent;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class JsonResponder implements PrioritisedResponseHandlerInterface
{
    use AcceptPriorityTrait;

    public function getSupportedPayloadTypes(): array
    {
        return [
            'array',
            'object',
        ];
    }

    protected function getSupportedContentTypes(): array
    {
        return ['application/json'];
    }

    public function handleResponsePayload(ResponsePayloadEvent $payloadEvent)
    {
        if (!($payloadEvent->payload instanceof Response)) {
            try {
                $payloadEvent->payload = new JsonResponse(
                    \json_encode($payloadEvent->payload, \JSON_THROW_ON_ERROR),
                    $payloadEvent->httpStatus ?? 200,
                    $payloadEvent->httpHeaders->all(),
                    true,
                );
                $payloadEvent->stopPropagation = true;
            } catch (JsonException $e) {
            }
        }
    }
}
