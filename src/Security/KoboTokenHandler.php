<?php

namespace App\Security;

use App\Entity\KoboDevice;
use App\Repository\KoboDeviceRepository;
use App\Security\Badge\KoboDeviceBadge;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;

class KoboTokenHandler implements KoboAccessTokenHandlerInterface
{
    public const TOKEN_KOBO_ATTRIBUTE = 'koboDevice';

    public function __construct(
        private readonly KoboDeviceRepository $repository,
    ) {
    }

    #[\Override]
    public function getKoboDeviceBadgeFrom(#[\SensitiveParameter] string $token): KoboDeviceBadge
    {
        if (trim($token) === '') {
            throw new BadCredentialsException('Invalid credentials.');
        }

        $koboDevice = $this->repository->byAccessKey($token);
        if (!$koboDevice instanceof KoboDevice) {
            throw new BadCredentialsException('Invalid credentials.');
        }

        return new KoboDeviceBadge($koboDevice);
    }
}
