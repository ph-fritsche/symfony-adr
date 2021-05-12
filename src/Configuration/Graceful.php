<?php
namespace Pitch\AdrBundle\Configuration;

use Attribute;
use Doctrine\Common\Annotations\Annotation;
use Doctrine\Common\Annotations\Annotation\Target;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
#[Attribute(Attribute::IS_REPEATABLE | Attribute::TARGET_METHOD)]
class Graceful extends ConfigurationAnnotation
{
    public ?string $value = null;

    public array $not = [];
}
