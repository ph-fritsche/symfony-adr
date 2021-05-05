<?php
namespace Pitch\AdrBundle\Configuration;

use RuntimeException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ConfigurationInterface;

abstract class ConfigurationAnnotation implements ConfigurationInterface
{
    const ALLOW_ARRAY = false;
    const ALIAS_NAME = null;

    public function __construct(
        array $values = []
    ) {
        foreach ($values as $propertyName => $v) {
            $setterName = 'set' . \ucwords($propertyName);
            if (\method_exists($this, $setterName)) {
                $this->$setterName($v);
            } elseif (\property_exists($this, $propertyName)) {
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

    public function allowArray(): bool
    {
        return static::ALLOW_ARRAY;
    }

    public function getAliasName(): ?string
    {
        return static::ALIAS_NAME;
    }
}
