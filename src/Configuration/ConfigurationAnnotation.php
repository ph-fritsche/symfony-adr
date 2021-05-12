<?php
namespace Pitch\AdrBundle\Configuration;

use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionType;
use RuntimeException;

abstract class ConfigurationAnnotation
{
    public function __construct(
        array $values = []
    ) {
        foreach ($values as $propertyName => $v) {
            $setterName = 'set' . \ucwords($propertyName);
            if (\method_exists($this, $setterName)) {
                $reflMethod = new ReflectionMethod($this, $setterName);

                $this->typecast(
                    $v,
                    $reflMethod->getNumberOfParameters() >= 1
                        ? $reflMethod->getParameters()[0]->getType()
                        : null,
                );

                $this->$setterName($v);
            } elseif (\property_exists($this, $propertyName)) {
                $reflProp = new ReflectionProperty($this, $propertyName);

                $this->typecast($v, $reflProp->getType());

                $this->$propertyName = $v;
            } else {
                throw new RuntimeException(\sprintf(
                    'Unknown key "%s" for annotation "@%s".',
                    $propertyName,
                    \get_class($this)
                ));
            }
        }
    }

    private function typecast(
        &$value,
        ?ReflectionType $type
    ) {
        if ($type instanceof ReflectionNamedType
            && gettype($value) !== $type->getName()
            && !is_a($value, $type->getName())
        ) {
            settype($value, $type->getName());
        }
    }
}
