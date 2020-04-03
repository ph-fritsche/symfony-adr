<?php
namespace nextdev\AdrBundle\Command;

use nextdev\AdrBundle\Responder\Responder;
use nextdev\AdrBundle\Util\ClassFinder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

class ResponderDebugCommandTest extends KernelTestCase
{
    protected $handlerMap = [
        'string' => [
            ['StringHandler', 123],
        ],
        'Error' => [
            ['ErrorHandler', 456],
        ],
        'ArgumentCountError' => [
            ['ArgumentCountErrorHandler', 789],
            ['ArgumentCountErrorHandler2', 789],
        ],
        'TypeError' => [
            ['TypeErrorHandler', 0],
        ],
        'NotLoadableFooClass' => [
            ['FooHandler', 0],
        ],
        'object' => [
            ['ObjectHandler', 0],
        ],
    ];

    public function provideListHandlers(): array
    {
        return [
            [
                null,
                null,
                \array_keys($this->handlerMap),
            ],
            [
                'string',
                'string',
                ['string'],
            ],
            [
                'integer',
                'int',
                [],
            ],
            [
                'foo',
                null,
                [],
                [],
                null,
                ['No matching class'],
                1
            ],
            [
                'foo',
                'ArgumentCountError',
                ['ArgumentCountError', 'TypeError', 'Error', 'object'],
                ['ArgumentCountError'],
                null,
                [],
            ],
            [
                'foo',
                'Error',
                ['Error', 'object'],
                ['Error', 'Bar'],
                'Error',
                ['Best match out of 2'],
            ],
            [
                'foo',
                'Error',
                ['Error', 'object'],
                ['Error', 'Bar'],
                null,
                ['Multiple matching'],
            ],
            [
                'foo',
                'NotLoadableFooClass',
                ['NotLoadableFooClass', 'object'],
                ['NotLoadableFooClass'],
                null,
                ['handlers might be incomplete'],
            ],
        ];
    }

    /**
     * @dataProvider provideListHandlers
     */
    public function testListHandlers(
        ?string $type,
        ?string $expectedType,
        array $expectedList,
        array $foundClasses = [],
        ?string $bestMatch = null,
        array $expectedNotices = [],
        int $expectedStatus = 0
    ): void {
        $responder = $this->createMock(Responder::class);
        $responder->method('getHandlerMap')->willReturn($this->handlerMap);
        /** @var Responder $responder */

        $finder = $this->createMock(ClassFinder::class);
        $finder->method('findClasses')->with($type)->willReturn($foundClasses);
        $finder->method('findBestMatch')->with($type, $foundClasses)->willReturn($bestMatch);
        /** @var ClassFinder $finder */

        $command = new ResponderDebugCommand($responder, $finder);
        $tester = new CommandTester($command);

        $tester->setInputs(['0']);

        $tester->execute(['type' => $type]);

        $this->assertEquals($expectedStatus, $tester->getStatusCode());

        $output = $tester->getDisplay();

        if ($expectedType !== null) {
            $this->assertStringContainsString('Response handlers for ' . $expectedType, $output);
        }

        foreach ($expectedList as $t) {
            $this->assertStringContainsString($t, $output);
            foreach ($this->handlerMap[$t] as $h) {
                $this->assertStringContainsString($h[0], $output);
            }
        }

        foreach ($expectedNotices as $n) {
            $this->assertStringContainsString($n, $output);
        }
    }
}
