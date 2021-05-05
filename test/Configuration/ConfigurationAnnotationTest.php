<?php
namespace Pitch\AdrBundle\Configuration;

use RuntimeException;

class ConfigurationAnnotationTest extends \PHPUnit\Framework\TestCase
{
    public function testConfigurationInterface()
    {
        $testClass = new class() extends ConfigurationAnnotation {
            const ALIAS_NAME = 'foo';
            const ALLOW_ARRAY = true;
        };

        $this->assertEquals($testClass::ALIAS_NAME, $testClass->getAliasName());
        $this->assertEquals($testClass::ALLOW_ARRAY, $testClass->allowArray());
    }

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
