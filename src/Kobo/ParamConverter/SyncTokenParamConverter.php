<?php

namespace App\Kobo\ParamConverter;

use App\Kobo\SyncToken;
use App\Service\KoboSyncTokenExtractor;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

#[AutoconfigureTag('controller.argument_value_resolver', ['priority' => 150])]
class SyncTokenParamConverter implements ValueResolverInterface
{
    public function __construct(protected KoboSyncTokenExtractor $koboSyncTokenExtractor)
    {
    }

    public function supports(ArgumentMetadata $configuration): bool
    {
        return $configuration->getType() === SyncToken::class;
    }

    public function apply(Request $request): SyncToken
    {
        // Fetch SyncToken from HTTP headers
        return $this->koboSyncTokenExtractor->get($request);
    }

    /**
     * @return iterable<SyncToken>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if ($this->supports($argument) === false) {
            return [];
        }

        return [$this->apply($request)];
    }
}
