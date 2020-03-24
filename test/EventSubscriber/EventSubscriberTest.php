<?php
namespace nextdev\AdrBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

abstract class EventSubscriberTest extends \PHPUnit\Framework\TestCase
{
    abstract protected function getSubscriberObject(): EventSubscriberInterface;

    public function testEventSubscriberInterface()
    {
        $eventSubscriber = $this->getSubscriberObject();
        $subscribedEvents = $eventSubscriber->getSubscribedEvents();

        $this->assertIsArray($subscribedEvents);

        foreach ($subscribedEvents as $eventId => $handlerDescriptor) {
            if (\is_string($handlerDescriptor)) {
                $handlerDescriptor = [$handlerDescriptor];
            }

            $this->assertIsArray($handlerDescriptor);

            if (\is_string($handlerDescriptor[0])) {
                $handlerDescriptor = [$handlerDescriptor];
            }

            $this->assertIsArray($handlerDescriptor);

            foreach ($handlerDescriptor as $desriptor) {
                $this->assertIsString($desriptor[0]);
                $this->assertIsCallable([$eventSubscriber, $desriptor[0]]);
            }
        }
    }
}
