<?php
namespace Pitch\AdrBundle\Responder\Handler;

use Pitch\AdrBundle\Responder\ResponseHandlerInterface;
use Pitch\AdrBundle\Responder\ResponsePayloadEvent;

abstract class ResponseHandlerTest extends \PHPUnit\Framework\TestCase
{
    public function testGetSupportedPayloadTypes()
    {
        $handler = $this->getResponseHandler();

        $supportedTypes = $handler->getSupportedPayloadTypes();

        $this->assertIsArray($supportedTypes);
        $this->assertContainsOnly('string', $supportedTypes);
    }

    abstract public function provideHandleResponsePayload(): array;

    /**
     * @dataProvider provideHandleResponsePayload
     */
    public function testHandleResponsePayload(
        $payload,
        $expectedPayload,
        $expectedStop = false
    ) {
        $event = new ResponsePayloadEvent();
        $handler = $this->getResponseHandler();

        $event->payload = $payload;
        $handler->handleResponsePayload($event);

        $this->assertEquals($expectedPayload, $event->payload);
        $this->assertEquals($expectedStop, $event->stopPropagation);
    }

    abstract protected function getResponseHandler(): ResponseHandlerInterface;
}
