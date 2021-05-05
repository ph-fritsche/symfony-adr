<?php
namespace Pitch\AdrBundle\Responder;

use stdClass;
use Symfony\Component\HttpFoundation\Response;

class ObjectHandlerTest extends ResponseHandlerTest
{
    public function provideHandleResponsePayload(): array
    {
        return [
            [
                $o = new stdClass(),
                [stdClass::class => $o],
            ],
            [
                $o = new ObjectHandler(),
                ['ObjectHandler' => $o],
            ],
            [
                'foo',
                ['string' => 'foo'],
            ],
            [
                $o = new Response(),
                $o,
            ],
        ];
    }

    protected function getResponseHandler(): ResponseHandlerInterface
    {
        return new ObjectHandler();
    }
}
