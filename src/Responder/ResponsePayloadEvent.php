<?php
namespace Pitch\AdrBundle\Responder;

use Symfony\Component\HttpFoundation\Request;

class ResponsePayloadEvent
{
    public $payload;

    public bool $stopPropagation = false;
    
    public Request $request;
}
