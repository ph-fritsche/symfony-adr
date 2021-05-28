<?php
namespace Pitch\AdrBundle\Responder;

use Pitch\AdrBundle\Responder\PrioritisedResponseHandlerInterface;
use Pitch\AdrBundle\Responder\ResponsePayloadEvent;

class TestPrioritisedResponseHandler extends TestResponseHandler implements
    PrioritisedResponseHandlerInterface
{
    /** @var float[]|float */
    protected $priorities;
    private $priorityCount = 0;

    /**
     * @param array[] $descriptions
     * @param float[]|float $priorities
     */
    public function __construct(
        string $key,
        HandlerPositionsAssert $positionAssert,
        array $descriptions,
        $priorities
    ) {
        parent::__construct($key, $positionAssert, $descriptions);
        $this->priorities = $priorities;
    }

    public function getResponseHandlerPriority(ResponsePayloadEvent $event): ?float
    {
        return \is_array($this->priorities) ? $this->priorities[$this->priorityCount++] : $this->priorities;
    }
}
