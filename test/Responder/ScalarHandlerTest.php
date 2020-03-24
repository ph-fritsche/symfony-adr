<?php
namespace nextdev\AdrBundle\Responder;

class ScalarHandlerTest extends ResponseHandlerTest
{
    public function provideHandleResponsePayload(): array
    {
        return [
            [
                'foo',
                ['value' => 'foo'],
            ],
        ];
    }

    protected function getResponseHandler(): ResponseHandlerInterface
    {
        return new ScalarHandler();
    }
}
