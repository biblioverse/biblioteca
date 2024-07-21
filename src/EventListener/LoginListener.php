<?php

namespace App\EventListener;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Bundle\SecurityBundle\Security\FirewallConfig;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Event\AuthenticationSuccessEvent;

final class LoginListener
{
    public function __construct(private EntityManagerInterface $entityManager, private RequestStack $requestStack, private Security $security)
    {
    }

    #[AsEventListener(event: 'security.authentication.success')]
    public function onSecurityAuthenticationSuccess(AuthenticationSuccessEvent $event): void
    {
        // ...
        $user = $event->getAuthenticationToken()->getUser();
        if (!$user instanceof User) {
            return;
        }
        $user->setLastLogin(new \DateTime());
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request || !$request->hasSession()) {
            return;
        }
        $config = $this->security->getFirewallConfig($request);
        if ($config instanceof FirewallConfig && $config->isStateless()) {
            return;
        }

        $this->requestStack->getSession()->set('_locale', $user->getLanguage() ?? 'en');
    }
}
