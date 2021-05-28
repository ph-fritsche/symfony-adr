<?php
namespace Pitch\AdrBundle\Responder;

use Symfony\Component\DependencyInjection\ContainerInterface;

class Responder
{
    const TYPETRANSLATE = [
        'boolean' => 'bool',
        'integer' => 'int',
        'double' => 'float',
        'resource (closed)' => 'resource',
        'NULL' => 'null',
    ];

    private ContainerInterface $container;

    /**
     * @var
     * [ typeFoo => [HandlerClass0, [HandlerClass1, ...], HandlerClass2, ...] ]
     */
    private array $handlerMap = [];

    /**
     * @var ResponseHandlerInterface[] $handlerObjects
     * [id => object]
     */
    private array $handlerObjects = [];

    public function __construct(
        ContainerInterface $container,
        array $handlerMap,
        array $handlerObjects
    ) {
        $this->container = $container;
        $this->handlerMap = $handlerMap;
        $this->handlerObjects = $handlerObjects;
    }

    public function getHandlerMap(): array
    {
        return $this->handlerMap;
    }

    public function handleResponsePayload(
        ResponsePayloadEvent $payloadEvent
    ) {
        $usedHandlersPayload = [];
        $usedHandlersLog = [];

        do {
            if (\is_object($payloadEvent->payload)) {
                $types = [
                    \get_class($payloadEvent->payload),
                    ...\array_values(\class_parents($payloadEvent->payload)),
                    ...\array_values(\class_implements($payloadEvent->payload)),
                    'object',
                ];
            } else {
                $t = \gettype($payloadEvent->payload);
                $types = [static::TYPETRANSLATE[$t] ?? $t];
            }

            foreach ($types as $t) {
                $stack = $this->handlerMap[$t] ?? [];
                
                do {
                    $prioritisedHandlers = [];
                    $currentHandler = null;
                    while ($stackEntry = \current($stack)) {
                        $serviceId = \is_array($stackEntry)
                            ? $stackEntry['name'] ?? $stackEntry[0]
                            : (string) $stackEntry;

                        if (!isset($this->handlerObjects[$serviceId])) {
                            $this->handlerObjects[$serviceId] = $this->container->get($serviceId);
                        }

                        \next($stack);

                        if ($this->handlerObjects[$serviceId] instanceof PrioritisedResponseHandlerInterface) {
                            $prioritisedHandlers[] = $serviceId;
                            continue;
                        } else {
                            $currentHandler = $serviceId;
                            break;
                        }
                    }

                    $handlers = \count($prioritisedHandlers)
                        ? $this->sortPrioritisedHandlers($prioritisedHandlers, $payloadEvent)
                        : [];
                    if (isset($currentHandler)) {
                        $handlers[] = $currentHandler;
                    }

                    $continueHandling = $this->applyHandlers(
                        $usedHandlersLog,
                        $usedHandlersPayload,
                        $t,
                        $handlers,
                        $payloadEvent,
                    );

                    if ($continueHandling === true) {
                        continue 3;
                    } elseif ($continueHandling === false) {
                        break 3;
                    }
                } while ($currentHandler !== null);
            }

            break;
        } while (true);

        return $payloadEvent->payload;
    }

    /**
     * @param string[] $prioritisedHandlers
     * @return string[]
     */
    protected function sortPrioritisedHandlers(
        array $prioritisedHandlers,
        ResponsePayloadEvent $responsePayloadEvent
    ): array {
        /** @var PrioritisedResponseHandlerInterface[] */
        $handlers = &$this->handlerObjects;
        $priorities = [];
        foreach ($prioritisedHandlers as $i => $id) {
            $priorities[$id] = $handlers[$id]->getResponseHandlerPriority($responsePayloadEvent);
            if ($priorities[$id] === null) {
                unset($prioritisedHandlers[$i]);
            }
        }

        uasort($prioritisedHandlers, fn(string $id0, string $id1) => $priorities[$id1] <=> $priorities[$id0]);

        return $prioritisedHandlers;
    }

    protected function applyHandlers(
        array &$usedHandlersLog,
        array &$usedHandlersPayload,
        string $type,
        array $handlers,
        ResponsePayloadEvent $payloadEvent
    ): ?bool {
        foreach ($handlers as $serviceId) {
            if (isset($usedHandlersPayload[$serviceId])
                && \in_array($payloadEvent->payload, $usedHandlersPayload[$serviceId], true)
            ) {
                throw new CircularHandlerException($usedHandlersLog);
            }

            $oldPayload = $payloadEvent->payload;

            $this->handlerObjects[$serviceId]->handleResponsePayload($payloadEvent);

            if ($payloadEvent->stopPropagation) {
                return false;
            }

            if ($payloadEvent->payload !== $oldPayload) {
                $usedHandlersPayload[$serviceId][] = $oldPayload;
                $usedHandlersLog[] = [$serviceId, $type];

                return true;
            }
        }
        return null;
    }
}
