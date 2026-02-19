<?php

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class TimezoneSubscriber implements EventSubscriberInterface
{
    private string $timezone;

    public function __construct(string $timezone = 'Africa/Douala')
    {
        $this->timezone = $timezone;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 100],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        date_default_timezone_set($this->timezone);
    }
}
