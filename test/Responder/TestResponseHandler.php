<?php
namespace Pitch\AdrBundle\Responder;

use Pitch\AdrBundle\Responder\ResponseHandlerInterface;
use Pitch\AdrBundle\Responder\ResponsePayloadEvent;

class TestResponseHandler implements ResponseHandlerInterface
{
    protected HandlerPositionsAssert $positionAssert;
    protected array $descriptions;
    protected string $key;
    protected int $callCount = 0;

    /**
     * @param array[] $descriptions
     */
    public function __construct(
        string $key,
        HandlerPositionsAssert $positionAssert,
        array $descriptions
    ) {
        $this->key = $key;
        $this->positionAssert = $positionAssert;
        $this->descriptions = $descriptions;
    }

    public function getSupportedPayloadTypes(): array
    {
        return [];
    }

    public function handleResponsePayload(
        ResponsePayloadEvent $event
    ) {
        $call = $this->callCount++;

        $this->positionAssert->check($this->key);

        if (\array_key_exists('set', $this->descriptions[$call])) {
            $event->payload = $this->descriptions[$call]['set'];
        }

        if (\array_key_exists('stop', $this->descriptions[$call])) {
            $event->stopPropagation = (bool) $this->descriptions[$call]['stop'];
        }
    }
}
