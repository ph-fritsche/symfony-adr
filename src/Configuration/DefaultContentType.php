<?php
namespace Pitch\AdrBundle\Configuration;

use Attribute;
use Doctrine\Common\Annotations\Annotation;
use Pitch\Annotation\AbstractAnnotation;

/**
 * @Annotation
 */
#[Attribute]
class DefaultContentType extends AbstractAnnotation
{
    public ?string $value = null;
}
