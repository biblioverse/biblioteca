<?php

namespace App\Security;

use App\Security\Badge\KoboDeviceBadge;

interface KoboAccessTokenHandlerInterface
{
    public function getKoboDeviceBadgeFrom(#[\SensitiveParameter] string $token): KoboDeviceBadge;
}
