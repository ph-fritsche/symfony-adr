<?php
namespace Pitch\AdrBundle\Responder;

interface PrioritisedResponseHandlerInterface extends ResponseHandlerInterface
{
    /**
     * Consecutive handlers implementing this interface will be executed in the order
     * of the return value of this function in descending order.
     * If you return `null`, the handler will be skipped.
     */
    public function getResponseHandlerPriority(ResponsePayloadEvent $event): ?float;
}
