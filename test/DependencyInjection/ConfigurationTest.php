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
                [],
                [
                    'graceful' => [
                        ['value' => RuntimeException::class],
                    ],
                ],
            ],
            [
                [
                    'graceful' => [
                        RuntimeException::class,
                    ]
                ],
                [
                    'graceful' => [
                        ['value' => RuntimeException::class, 'not' => []],
                    ],
                ],
            ],
            [
                [
                    'graceful' => [
                        ['value' => RuntimeException::class, 'not' => OutOfBoundsException::class],
                    ],
                ],
                [
                    'graceful' => [
                        ['value' => RuntimeException::class, 'not' => [OutOfBoundsException::class]],
                    ],
                ],
            ],
        ];
    }

    /**
     * @dataProvider provideNormalize
     */
    public function testNormalize(
        $config,
        $processedConfig
    ) {
        $processor = new Processor();

        $this->assertEquals($processedConfig, $processor->processConfiguration(new Configuration(), [$config]));
    }
}
