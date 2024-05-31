<?php

namespace App\Security;

use App\Repository\KoboDeviceRepository;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class KoboTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private KoboDeviceRepository $repository
    ) {
    }

    public function getUserBadgeFrom(string $token): UserBadge
    {
        if (trim($token) === '') {
            throw new BadCredentialsException('Invalid credentials.');
        }

        $koboDevice = $this->repository->byAccessKey($token);
        if (null === $koboDevice) {
            throw new BadCredentialsException('Invalid credentials.');
        }

        // and return a UserBadge object containing the user identifier from the found token
        $badgeIdentifier = sprintf('kobo-%s:%s', $koboDevice->getId(), $koboDevice->getUser()->getUserIdentifier());

        return new UserBadge($badgeIdentifier, function () use ($koboDevice) {
            $user = $koboDevice->getUser();
            $user->addRole('ROLE_KOBO');

            return $user;
        }, ['kobo' => [$koboDevice->getId()]]);
    }
}
