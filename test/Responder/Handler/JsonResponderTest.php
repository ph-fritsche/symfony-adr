<?php
namespace Pitch\AdrBundle\Responder\Handler;

use PHPUnit\Framework\TestCase;
use Pitch\AdrBundle\Responder\ResponsePayloadEvent;
use stdClass;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class JsonResponderTest extends TestCase
{
    public function testCreateJsonResponse()
    {
        $event = new ResponsePayloadEvent(['a' => 'b'], new Request());
        
        (new JsonResponder())->handleResponsePayload($event);

        $this->assertInstanceOf(JsonResponse::class, $event->payload);
        $this->assertEquals('{"a":"b"}', $event->payload->getContent());
    }

    public function testCatchJsonExceptions()
    {
        $circular = new stdClass();
        $circular->foo = $circular;

        $event = new ResponsePayloadEvent($circular, new Request());

        (new JsonResponder)->handleResponsePayload($event);

        $this->assertSame($circular, $event->payload);
    }
}
