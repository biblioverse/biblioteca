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

        $content = $event->getResponse()->getContent();

        if (!is_string($content)) {
            return;
        }

        try {
            $content = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $content = $event->getResponse()->getContent();
        }

        $this->koboLogger->info('Response from '.$event->getRequest()->getPathInfo(), ['response' => $content, 'headers' => $event->getResponse()->headers->all()]);
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

        $content = $event->getRequest()->getContent();

        try {
            $content = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $content = $event->getRequest()->getContent();
        }

        $this->koboLogger->info('Request on '.$event->getRequest()->getPathInfo(), ['request' => $content, 'headers' => $event->getRequest()->headers->all()]);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::CONTROLLER => 'onKernelController',
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }
}
