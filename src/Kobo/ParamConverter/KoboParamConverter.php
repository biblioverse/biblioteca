<?php

namespace App\Kobo\ParamConverter;

use App\Entity\KoboDevice;
use App\Repository\KoboDeviceRepository;
use App\Security\Token\PostAuthenticationTokenWithKoboDevice;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\PropertyAccess\PropertyAccessor;

#[AutoconfigureTag('controller.argument_value_resolver', ['priority' => 150])]
class KoboParamConverter implements ValueResolverInterface
{
    public function __construct(
        protected KoboDeviceRepository $bookRepository,
        protected Security $security,
    ) {
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        return $argument->getType() === KoboDevice::class && $this->getFieldValue($request) !== null;
    }

    public function apply(Request $request): ?KoboDevice
    {
        $value = $this->getFieldValue($request);
        if ($value === null) {
            return null;
        }

        $fieldName = $this->getFieldName();

        // Load the KoboDevice from the security token if it matches the parameter value
        $token = $this->security->getToken();
        if ($token instanceof PostAuthenticationTokenWithKoboDevice) {
            $loadedKobo = $token->getKoboDevice();
            $propertyAccessor = new PropertyAccessor();
            if ($propertyAccessor->isReadable($loadedKobo, $fieldName) && $propertyAccessor->getValue($loadedKobo, $fieldName) === $value) {
                return $loadedKobo;
            }
        }

        return $this->bookRepository->findOneBy([$fieldName => $value]);
    }

    /**
     * @return array<int, KoboDevice>
     */
    #[\Override]
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if ($this->supports($request, $argument) === false) {
            return [];
        }

        return array_filter([$this->apply($request)]);
    }

    private function getFieldValue(Request $request): ?string
    {
        /** @var array<string, string|int> $params */
        $params = $request->attributes->get('_route_params', []);
        $name = $this->getFieldName();

        return array_key_exists($name, $params) ? ((string) $params[$name]) : null;
    }

    private function getFieldName(): string
    {
        return 'accessKey';
    }
}
