<?php
namespace nextdev\AdrBundle\Action;

use LogicException;
use RuntimeException;
use OverflowException;
use OutOfBoundsException;
use nextdev\AdrBundle\Configuration\Graceful;

class ActionProxyTest extends \PHPUnit\Framework\TestCase
{
    public function provideCatchGraceful(): array
    {
        return [
            [
                [
                    new Graceful(['value' => LogicException::class]),
                    new Graceful(['value' => RuntimeException::class]),
                ],
                RuntimeException::class,
                false,
            ],
            [
                [
                    new Graceful(['value' => OverflowException::class]),
                    new Graceful(['value' => OutOfBoundsException::class]),

                ],
                LogicException::class,
                true,
            ],
            [
                [
                    new Graceful(['value' => RuntimeException::class, 'not' => [OverflowException::class]]),
                ],
                OverflowException::class,
                true,
            ],
            [
                [
                    new Graceful(['value' => RuntimeException::class, 'not' => [OverflowException::class]]),
                ],
                OutOfBoundsException::class,
                false,
            ],
            [
                [
                    new Graceful(['value' => RuntimeException::class]),
                    new Graceful(['not' => [OverflowException::class]]),
                ],
                OverflowException::class,
                true,
            ],
        ];
    }

    /**
     * @dataProvider provideCatchGraceful
     */
    public function testCatchGraceful(
        array $graceful,
        string $throw,
        bool $exceptException
    ) {
        $actionProxy = new ActionProxy(function () use ($throw) {
            throw new $throw();
        });
        $actionProxy->graceful = $graceful;

        if ($exceptException) {
            $this->expectException($throw);
            $actionProxy();
        } else {
            $this->assertInstanceOf($throw, $actionProxy());
        }
    }
}
