<?php

namespace App\Kobo\LogProcessor;

use App\Entity\KoboDevice;
use App\Kobo\ParamConverter\KoboParamConverter;
use Monolog\LogRecord;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class KoboContextProcessor
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly KoboParamConverter $koboParamConverter,
    ) {
    }

    public function __invoke(LogRecord $record): LogRecord
    {
        $request = $this->requestStack->getCurrentRequest();

        if (!$request instanceof Request) {
            return $record;
        }

        if (false === $request->attributes->has('isKoboRequest')) {
            return $record;
        }

        $kobo = $this->getKoboFromRequest($request);
        $koboString = $kobo?->getId() ?? 'unknown';
        $record->extra['kobo'] = $koboString;

        return $record;
    }

    private function getKoboFromRequest(Request $request): ?KoboDevice
    {
        $device = $request->attributes->get('koboDevice');
        if ($device instanceof KoboDevice) {
            return $device;
        }

        return $this->koboParamConverter->apply($request);
    }
}
