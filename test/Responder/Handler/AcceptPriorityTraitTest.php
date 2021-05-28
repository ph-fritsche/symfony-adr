<?php
namespace Pitch\AdrBundle\Responder\Handler;

use PHPUnit\Framework\TestCase;
use Pitch\AdrBundle\Configuration\DefaultContentType;
use Pitch\AdrBundle\Responder\ResponsePayloadEvent;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\Request;

class AcceptPriorityTraitTest extends TestCase
{
    public function provideRequestSettings()
    {
        return [
            'default to 1' => [
                1,
            ],
            'matching defaultContentType' => [
                1,
                ['foo/bar', 'foo/baz'],
                'foo/baz',
            ],
            'not matching defaultContentType' => [
                null,
                ['foo/bar'],
                'foo/baz',
            ],
            'matching accept' => [
                0.8,
                ['foo/bar'],
                null,
                'foo/baz;q=1,foo/bar;q=0.8',
            ],
            'not matching accept' => [
                null,
                ['foo/bar'],
                'foo/bar',
                'foo/baz',
            ],
            'accept any matching defaultContentType' => [
                0.8,
                ['foo/bar'],
                'foo/bar',
                'foo/baz;q=1,foo/bar;q=0.1,*/*;q=0.8',
            ],
            'accept any not matching defaultContentType' => [
                0,
                ['foo/bar'],
                'foo/baz',
                'foo/baz,foo/bar;q=0.1,*/*;q=0.8',
            ],
        ];
    }

    /**
     * @dataProvider provideRequestSettings
     */
    public function testGetPriorityFromAcceptQuality(
        ?float $expectedPriority,
        array $supportedContentTypes = [],
        ?string $defaultContentType = null,
        ?string $acceptHeader = null
    ) {
        $handler = $this->getMockForTrait(AcceptPriorityTrait::class);
        $handler->method('getSupportedContentTypes')->willReturn($supportedContentTypes);
        /** @var AcceptPriorityTrait $handler */

        $request = new Request();
        if ($acceptHeader) {
            $request->headers->set('accept', $acceptHeader);
        }
        if ($defaultContentType) {
            $request->attributes->set(
                '_' . DefaultContentType::class,
                new DefaultContentType($defaultContentType)
            );
        }

        $event = new ResponsePayloadEvent(null, $request);

        $this->assertSame($expectedPriority, $handler->getResponseHandlerPriority($event));
    }

    public function testStoreAccessHeaderOnRequestAttributes()
    {
        $handler = $this->getMockForTrait(AcceptPriorityTrait::class);
        $handler->method('getSupportedContentTypes')->willReturn(['foo/baz']);
        /** @var AcceptPriorityTrait $handler */

        $request = new Request();
        $request->headers->set('accept', 'foo/bar,foo/baz;q=0.2');
        $event = new ResponsePayloadEvent(null, $request);

        $this->assertEquals(0.2, $handler->getResponseHandlerPriority($event));

        /** @var AcceptHeader */
        $attr = $request->attributes->get(AcceptHeader::class);
        $this->assertInstanceOf(AcceptHeader::class, $attr);
        $this->assertEquals(0.2, $attr->get('foo/baz')->getQuality());

        $request->attributes->set(AcceptHeader::class, AcceptHeader::fromString('foo/baz;q=0.5'));

        $this->assertEquals(0.5, $handler->getResponseHandlerPriority($event));
    }
}
