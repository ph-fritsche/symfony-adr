<?php
namespace nextdev\AdrBundle\Responder;

use Symfony\Component\HttpFoundation\Request;
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

    /** @var
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

    public function handleResponsePayload(
        ResponsePayloadEvent $payloadEvent
    ) {
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
                foreach ($this->handlerMap[$t] ?? [] as $stackEntry) {
                    $serviceId = \is_array($stackEntry)? $stackEntry['name'] ?? $stackEntry[0] : (string) $stackEntry;

                    if (!isset($this->handlerObjects[$serviceId])) {
                        $this->handlerObjects[$serviceId] = $this->container->get($serviceId);
                    }
            
                    $oldPayload = $payloadEvent->payload;

                    $this->handlerObjects[$serviceId]->handleResponsePayload($payloadEvent);

                    if ($payloadEvent->stopPropagation) {
                        break 3;
                    }

                    if ($payloadEvent->payload !== $oldPayload) {
                        continue 3;
                    }
                }
            }

            break;
        } while (true);

        return $payloadEvent->payload;
    }
}
