<?php

namespace App\Security;

use App\Entity\OpdsAccess;
use App\Entity\User;
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

        $accessTokenObject = $this->repository->findOneByToken($accessToken);

        if (!$accessTokenObject instanceof OpdsAccess) {
            throw new BadCredentialsException('Invalid credentials.');
        }
        $user = $accessTokenObject->getUser();
        if (!$user instanceof User) {
            throw new BadCredentialsException('Invalid credentials.');
        }

        return new UserBadge($user->getUserIdentifier());
    }
}
