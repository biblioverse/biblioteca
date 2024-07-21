<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class LanguageListener
{
    public function __construct(private RequestStack $requestStack)
    {
    }

    #[AsEventListener(event: KernelEvents::REQUEST)]
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$this->requestStack->getCurrentRequest() instanceof Request || !$this->requestStack->getCurrentRequest()->hasSession()) {
            return;
        }

        $request->setLocale(''.$this->requestStack->getSession()->get('_locale'));
    }
}
