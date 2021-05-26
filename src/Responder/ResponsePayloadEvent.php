<?php
namespace Pitch\AdrBundle\Responder;

use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

class ResponsePayloadEvent
{
    public $payload;

    public bool $stopPropagation = false;
    
    public Request $request;

    public ?int $httpStatus = null;
    public HeaderBag $httpHeaders;

    public ParameterBag $attributes;

    public function __construct(
        $payload,
        Request $request
    ) {
        $this->payload = $payload;
        $this->request = $request;

        $this->httpHeaders = new HeaderBag();
        $this->attributes = new ParameterBag();
    }
}
