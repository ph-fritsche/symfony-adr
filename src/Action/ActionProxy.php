<?php
namespace Pitch\AdrBundle\Action;

use Throwable;
use Pitch\AdrBundle\Configuration\Graceful;

class ActionProxy
{
    private $action;

    /** @var Graceful[] */
    public array $graceful = [];

    public function __construct(
        callable $action
    ) {
        $this->action = $action;
    }

    public function __invoke(
        ...$arguments
    ) {
        try {
            $responsePayload = ($this->action)(...$arguments);
        } catch (Throwable $exception) {
            $exceptionType = [
                \get_class($exception),
                ...\array_values(\class_parents($exception)),
                ...\array_values(\class_implements($exception))
            ];
            $isGraceful = null;

            foreach ($this->graceful as $g) {
                if (isset($g->value)) {
                    if ($isGraceful !== true
                        && \in_array($g->value, $exceptionType)
                        && \count(\array_intersect($exceptionType, $g->not)) === 0
                    ) {
                        $isGraceful = true;
                    }
                } elseif (\count(\array_intersect($exceptionType, $g->not)) > 0) {
                    $isGraceful = false;
                }
            }

            if (!$isGraceful) {
                throw $exception;
            }

            $responsePayload = $exception;
        }
        
        return $responsePayload;
    }
}
