<?php

namespace App\EventSubscriber;

use App\Controller\Kobo\AbstractKoboController;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class KoboRequestSubscriber implements EventSubscriberInterface
{
    public function __construct(protected LoggerInterface $koboLogger)
    {
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (false === (bool) $event->getRequest()->attributes->get('isKoboRequest', false)) {
            return;
        }

        $this->koboLogger->info('Response from '.$event->getRequest()->getPathInfo(), ['response' => $event->getResponse()->getContent(), 'headers' => $event->getResponse()->headers->all()]);
    }

    public function onKernelController(ControllerEvent $event): void
    {
        $controller = $event->getController();

        if (is_array($controller)) {
            $controller = $controller[0];
        }

        if (!$controller instanceof AbstractKoboController) {
            return;
        }
        $event->getRequest()->attributes->set('isKoboRequest', true);

        $this->koboLogger->info('Request on '.$event->getRequest()->getPathInfo(), ['response' => $event->getRequest()->getContent(), 'headers' => $event->getRequest()->headers->all()]);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }
}
