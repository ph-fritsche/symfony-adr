<?php
namespace Pitch\AdrBundle\DependencyInjection\Compiler;

use ReflectionClass;
use Pitch\AdrBundle\Responder\Responder;
use Symfony\Component\DependencyInjection\Reference;
use Pitch\AdrBundle\Responder\ResponseHandlerInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Pitch\AdrBundle\DependencyInjection\PitchAdrExtension;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException;
use Throwable;

class ResponseHandlerPass implements CompilerPassInterface
{
    const TAG = PitchAdrExtension::ALIAS . '.' . 'responder';

    private ?PitchAdrExtension $extension;

    public function __construct(
        ?PitchAdrExtension $extension = null
    ) {
        $this->extension = $extension;
    }

    public function process(
        ContainerBuilder $container
    ): void {
        if (!$container->has(Responder::class)) {
            return;
        }

        $responder = $container->findDefinition(Responder::class);

        $handlerMap = [];
        $handlerObjects = [];

        foreach ($container->getDefinitions() as $serviceId => $definition) {
            if ($definition->isAbstract()) {
                continue;
            }

            $serviceClass = $definition->getClass();
            if (isset($serviceClass)) {
                try {
                    if (!\class_exists($serviceClass)) {
                        continue;
                    }
                } catch (Throwable $e) {
                    continue;
                }

                if (!\in_array(ResponseHandlerInterface::class, \class_implements($serviceClass))) {
                    continue;
                }
                
                if (\count($definition->getTag(static::TAG)) === 0) {
                    $definition->addTag(static::TAG);
                }
            }

            $tags = $definition->getTag(static::TAG);

            if (\count($tags) === 0) {
                continue;
            }

            $service = $container->get($serviceId);
            $supportedTypes = $this->normalizeSupportedPayloadTypes($service->getSupportedPayloadTypes());

            foreach ($tags as $tag) {
                $diff = \array_diff($tag['for'] ?? [], \array_keys($supportedTypes));
                if (\count($diff)) {
                    throw new LogicException(\sprintf(
                        'ResponseHandler %s only supports the following types: %s',
                        $serviceId,
                        \implode(', ', \array_keys($supportedTypes))
                    ));
                }

                foreach ($tag['for'] ?? \array_keys($supportedTypes) as $t) {
                    $handlerMap[Responder::TYPETRANSLATE[$t] ?? $t][] = [
                        $serviceId,
                        $tag['priority'] ?? $supportedTypes[$t]
                    ];
                }
            }

            // public services can be retrieved from container after compilation
            if ($definition->isPrivate() || !$definition->isPublic()) {
                $handlerObjects[$serviceId] = new Reference($serviceId);
            }
        }

        \array_walk($handlerMap, function (&$handlerStack) {
            \usort($handlerStack, fn($a, $b) => $b[1] <=> $a[1]);
        });

        $responder->setArgument('$container', new Reference(ContainerInterface::class));
        $responder->setArgument('$handlerMap', $handlerMap);
        $responder->setArgument('$handlerObjects', $handlerObjects);
    }

    private function normalizeSupportedPayloadTypes(
        array $supportedTypes
    ): array {
        $normalized = [];

        foreach ($supportedTypes as $k => $v) {
            if (\is_string($k)) {
                $normalized[$k] = (int) $v;
            } else {
                $normalized[$v] = 0;
            }
        }

        return $normalized;
    }
}
