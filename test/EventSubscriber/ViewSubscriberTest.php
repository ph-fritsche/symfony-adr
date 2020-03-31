<?php
namespace nextdev\AdrBundle\EventSubscriber;

use nextdev\AdrBundle\Responder\Responder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use nextdev\AdrBundle\Responder\ResponsePayloadEvent;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class ViewSubscriberTest extends EventSubscriberTest
{
    public function provideRelayPayload(): array
    {
        return [
            ['bar'],
            [new Response()],
        ];
    }

    /**
     * @dataProvider provideRelayPayload
     */
    public function testRelayPayload(
        $returnPayload
    ) {
        $payload = 'foo';
        $request = new Request();
        $viewEvent = new ViewEvent(
            $this->createMock(HttpKernelInterface::class),
            $request,
            HttpKernelInterface::MASTER_REQUEST,
            $payload
        );

        $responderMock = $this->createMock(Responder::class);
        $responderMock->expects($this->once())->method('handleResponsePayload')
            ->with($this->callback(function ($event) use ($payload, $request) {
                return $event instanceof ResponsePayloadEvent
                    && $event->payload === $payload
                    && $event->request === $request;
            }))
            ->willReturn($returnPayload);

        $this->getSubscriberObject($responderMock)->onKernelView($viewEvent);

        $this->assertEquals(
            $returnPayload,
            $viewEvent->isPropagationStopped() && $viewEvent->hasResponse() ?
                $viewEvent->getResponse() :
                $viewEvent->getControllerResult()
        );
    }

    protected function getSubscriberObject(
        Responder $responderMock = null
    ): ViewSubscriber {
        return new ViewSubscriber($responderMock ?? $this->createMock(Responder::class));
    }
}
