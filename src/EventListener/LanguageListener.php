<?php

namespace App\EventListener;

use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bundle\SecurityBundle\Security\FirewallConfig;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final readonly class LanguageListener
{
    public function __construct(private RequestStack $requestStack, private Security $security)
    {
    }

    #[AsEventListener(event: KernelEvents::REQUEST)]
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$request->hasSession()) {
            return;
        }
        $config = $this->security->getFirewallConfig($request);
        if ($config instanceof FirewallConfig && $config->isStateless()) {
            return;
        }

        $locale = $this->requestStack->getSession()->get('_locale');
        if (!is_string($locale)) {
            return;
        }

        $request->setLocale($locale);
    }
}
