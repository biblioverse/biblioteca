<?php

namespace App\Security\Token;

use App\Entity\KoboDevice;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

class PostAuthenticationTokenWithKoboDevice extends PostAuthenticationToken
{
    public function __construct(private readonly KoboDevice $device, UserInterface $user, string $firewallName, array $roles)
    {
        parent::__construct($user, $firewallName, $roles);
    }

    public function getKoboDevice(): KoboDevice
    {
        return $this->device;
    }
}
