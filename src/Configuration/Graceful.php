<?php
namespace Pitch\AdrBundle\Configuration;

use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class Graceful extends ConfigurationAnnotation
{
    const ALLOW_ARRAY = true;
    const ALIAS_NAME = 'gracefulException';

    public ?string $value = null;

    public array $not = [];
}
