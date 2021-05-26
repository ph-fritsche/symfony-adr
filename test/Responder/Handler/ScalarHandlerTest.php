<?php
namespace Pitch\AdrBundle\Responder\Handler;

use Pitch\AdrBundle\Responder\ResponseHandlerInterface;

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
