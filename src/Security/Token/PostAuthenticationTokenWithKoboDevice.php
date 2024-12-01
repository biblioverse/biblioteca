<?php

namespace App\Security\Token;

use App\Entity\KoboDevice;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

class PostAuthenticationTokenWithKoboDevice extends PostAuthenticationToken
{
    private KoboDevice $device;

    public function __construct(KoboDevice $device, UserInterface $user, string $firewallName, array $roles)
    {
        parent::__construct($user, $firewallName, $roles);
        $this->device = $device;
    }

    public function getKoboDevice(): KoboDevice
    {
        return $this->device;
    }
}
