<?php
namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

class DisplayModeSubscriber implements EventSubscriberInterface
{

    private string $displayMode;


    public function onKernelRequest(RequestEvent $event): void
    {
        $this->displayMode = $event->getRequest()->cookies->get('displayMode','gallery');
        $this->displayMode = $event->getRequest()->query->get('displayMode',$this->displayMode);

    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();
        $response->headers->setCookie(new Cookie('displayMode', $this->displayMode, time() + 30*24*3600, '/', null, false, false));
    }

    /**
     * @return string
     */
    public function getDisplayMode(): string
    {
        return $this->displayMode;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

}