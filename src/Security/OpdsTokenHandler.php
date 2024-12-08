<?php

namespace App\Security;

use App\Repository\OpdsAccessRepository;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Http\AccessToken\AccessTokenHandlerInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;

class OpdsTokenHandler implements AccessTokenHandlerInterface
{
    public function __construct(
        private readonly OpdsAccessRepository $repository,
    ) {
    }

    public function getUserBadgeFrom(?string $accessToken): UserBadge
    {
        if (null === $accessToken) {
            throw new BadCredentialsException('Invalid credentials.');
        }

        $accessToken = $this->repository->findOneBy(['token' => $accessToken]);

        if (null === $accessToken) {
            throw new BadCredentialsException('Invalid credentials.');
        }

        return new UserBadge($accessToken->getUser()->getUserIdentifier());
    }
}
