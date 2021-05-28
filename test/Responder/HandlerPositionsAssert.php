<?php
namespace Pitch\AdrBundle\Responder;

use PHPUnit\Framework\Assert;

class HandlerPositionsAssert
{
    private $expectedHandlers;
    private $actualHandlers = [];

    private $assertMissing = true;

    public function __construct($expectedHandlers)
    {
        $this->expectedHandlers = $expectedHandlers;
    }

    public function __destruct()
    {
        if ($this->assertMissing && \count($this->expectedHandlers) !== \count($this->actualHandlers)) {
            Assert::assertEquals(
                \array_column($this->expectedHandlers, 0),
                $this->actualHandlers,
                'Expected handler calls missing.'
            );
        }
    }

    public function check(
        string $key
    ): void {
        $this->assertMissing = false;
        Assert::assertEquals(
            $this->expectedHandlers[\count($this->actualHandlers)][0] ?? null,
            $key,
            \sprintf('Unexpected handler call at position %d', \count($this->actualHandlers))
        );
        $this->assertMissing = true;

        $this->actualHandlers[] = $key;
    }
}
