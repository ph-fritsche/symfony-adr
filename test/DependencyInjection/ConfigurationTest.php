<?php
namespace Pitch\AdrBundle\DependencyInjection;

use RuntimeException;
use OutOfBoundsException;
use Symfony\Component\Config\Definition\Processor;

class ConfigurationTest extends \PHPUnit\Framework\TestCase
{
    public function provideNormalize(): array
    {
        return [
            [
                null,
                [
                    ['value' => RuntimeException::class],
                ],
            ],
            [
                [
                    RuntimeException::class,
                ],
                [
                    ['value' => RuntimeException::class, 'not' => []],
                ],
            ],
            [
                [
                    ['value' => RuntimeException::class, 'not' => OutOfBoundsException::class],
                ],
                [
                    ['value' => RuntimeException::class, 'not' => [OutOfBoundsException::class]],
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideNormalize
     */
    public function testNormalizeGraceful(
        $config,
        $expectedProcessedGraceful
    ) {
        $processedConfig = $this->processConfig(isset($config) ? [
            'graceful' => $config
        ] : []);

        $this->assertArrayHasKey('graceful', $processedConfig);
        $this->assertEquals($expectedProcessedGraceful, $processedConfig['graceful']);
    }

    public function testDefaultResponseHandlers()
    {
        $this->assertTrue($this->processConfig()['defaultResponseHandlers']);

        $this->assertFalse($this->processConfig([
            'defaultResponseHandlers' => false,
        ])['defaultResponseHandlers']);
    }

    private function processConfig(
        array ...$configs
    ) {
        return (new Processor())->processConfiguration(
            new Configuration(),
            $configs,
        );
    }
}
