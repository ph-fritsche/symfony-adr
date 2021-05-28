<?php
namespace Pitch\AdrBundle\Responder\Handler;

use Pitch\AdrBundle\Configuration\DefaultContentType;
use Pitch\AdrBundle\Responder\ResponsePayloadEvent;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\Request;

trait AcceptPriorityTrait
{
    public function getResponseHandlerPriority(ResponsePayloadEvent $event): ?float
    {
        return $this->getAcceptPriority($event->request);
    }

    /**
     * @return string[]
     */
    abstract protected function getSupportedContentTypes(): array;

    protected function getAcceptPriority(
        Request $request
    ): ?float {
        $accept = $this->getRequestAcceptHeader($request);

        if ($accept) {
            foreach ($accept->all() as $a) {
                $v = $a->getValue();
                if ($v === '*/*') {
                    return $this->supportsDefaultContentType($request) ? $a->getQuality() : 0;
                } elseif (\in_array($v, $this->getSupportedContentTypes())) {
                    return $a->getQuality();
                }
            }
            return null;
        }

        return $this->supportsDefaultContentType($request) ? 1 : null;
    }

    private function supportsDefaultContentType(
        Request $request
    ): bool {
        $defaultType = $request->attributes->has('_' . DefaultContentType::class)
            ? $request->attributes->get('_' . DefaultContentType::class)
            : null;

        return $defaultType instanceof DefaultContentType
            ? \in_array($defaultType->value, $this->getSupportedContentTypes())
            : true;
    }

    private function getRequestAcceptHeader(
        Request $request
    ): ?AcceptHeader {
        if ($request->attributes->has(AcceptHeader::class)) {
            $accept = $request->attributes->get(AcceptHeader::class);
        } else {
            $accept = $request->headers->has('accept')
                ? AcceptHeader::fromString($request->headers->get('accept'))
                : null;

            $request->attributes->set(AcceptHeader::class, $accept);
        }

        return $accept;
    }
}
