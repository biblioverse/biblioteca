<?php

namespace App\Kobo\ParamConverter;

use App\Kobo\SyncToken\SyncTokenInterface;
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
        return $configuration->getType() === SyncTokenInterface::class;
    }

    public function apply(Request $request): SyncTokenInterface
    {
        // Fetch SyncToken from HTTP headers
        return $this->koboSyncTokenExtractor->get($request);
    }

    /**
     * @return iterable<?SyncTokenInterface>
     */
    #[\Override]
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if ($this->supports($argument) === false) {
            return [];
        }

        if ($argument->isNullable() && !$this->koboSyncTokenExtractor->has($request)) {
            return [null];
        }

        return [$this->apply($request)];
    }
}
