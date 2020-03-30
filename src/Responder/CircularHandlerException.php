<?php
namespace nextdev\AdrBundle\Responder;

use LogicException;

class CircularHandlerException extends LogicException
{
    public function __construct(array $log)
    {
        $this->message = \sprintf(
            "The response handlers changed the payload circularly:\n%s",
            \implode("\n", \array_map(fn($a) => $a[1] . "\t=> " . $a[0], $log))
        );
    }
}
