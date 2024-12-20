<?php

namespace App\Security\Badge;

use App\Entity\KoboDevice;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\BadgeInterface;

class KoboDeviceBadge implements BadgeInterface
{
    public function __construct(private readonly KoboDevice $device)
    {
    }

    #[\Override]
    public function isResolved(): bool
    {
        return true;
    }

    public function getDevice(): KoboDevice
    {
        return $this->device;
    }
}
