<?php
namespace nextdev\AdrBundle\Responder;

use stdClass;

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
            ]
        ];
    }

    protected function getResponseHandler(): ResponseHandlerInterface
    {
        return new ObjectHandler();
    }
}
