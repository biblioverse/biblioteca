<?php

namespace App\Kobo\ParamConverter;

use App\Kobo\SyncToken;
use App\Service\KoboSyncTokenExtractor;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;

class SyncTokenParamConverter implements ParamConverterInterface
{
    public function __construct(protected KoboSyncTokenExtractor $koboSyncTokenExtractor)
    {
    }

    public function supports(ParamConverter $configuration)
    {
        return $configuration->getClass() === SyncToken::class;
    }

    public function apply(Request $request, ParamConverter $configuration): bool
    {
        // Fetch SyncToken from HTTP headers
        $syncToken = $this->koboSyncTokenExtractor->get($request);

        $request->attributes->set($configuration->getName(), $syncToken);

        return true;
    }
}
