<?php

namespace App\Security;

use App\Repository\KoboRepository;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class KoboTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private KoboRepository $repository
    ) {
    }

    public function getUserBadgeFrom(string $token): UserBadge
    {
        if (trim($token) === '') {
            throw new BadCredentialsException('Invalid credentials.');
        }

        $kobo = $this->repository->byAccessKey($token);
        if (null === $kobo) {
            throw new BadCredentialsException('Invalid credentials.');
        }

        // and return a UserBadge object containing the user identifier from the found token
        $badgeIdentifier = sprintf('kobo-%s:%s', $kobo->getId(), $kobo->getUser()->getUserIdentifier());

        return new UserBadge($badgeIdentifier, function () use ($kobo) {
            $user = $kobo->getUser();
            $user->addRole('ROLE_KOBO');

            return $user;
        }, ['kobo' => [$kobo->getId()]]);
    }
}
