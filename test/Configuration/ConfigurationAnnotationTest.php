<?php
namespace Pitch\AdrBundle\Configuration;

use RuntimeException;

class ConfigurationAnnotationTest extends \PHPUnit\Framework\TestCase
{
    public function testConstructWithProperty()
    {
        $testClass = new class([
            'foo' => 'bar',
        ]) extends ConfigurationAnnotation {
            public $foo;
        };

        $this->assertEquals('bar', $testClass->foo);
    }

    public function testConstructWithSetter()
    {
        $testClass = new class([
            'foo' => 'bar',
        ]) extends ConfigurationAnnotation {
            public $foo;

            public function setFoo(
                string $value
            ) {
                $this->foo = $value . '-baz';
            }
        };

        $this->assertEquals('bar-baz', $testClass->foo);
    }

    public function testConstructWithException()
    {
        $this->expectException(RuntimeException::class);

        $testClass = new class([
            'foo' => 'bar',
        ]) extends ConfigurationAnnotation {
        };
    }
}
