<?php

namespace App\Kobo\ParamConverter;

use App\Entity\KoboDevice;
use App\Repository\KoboDeviceRepository;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

#[AutoconfigureTag('controller.argument_value_resolver', ['priority' => 150])]
class KoboParamConverter implements ValueResolverInterface
{
    public function __construct(protected KoboDeviceRepository $bookRepository)
    {
    }

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        return $argument->getType() === KoboDevice::class && $this->getFieldValue($request) !== null;
    }

    public function apply(Request $request): ?KoboDevice
    {
        $value = $this->getFieldValue($request);

        return $this->bookRepository->findOneBy([$this->getFieldName() => $value]);
    }

    /**
     * @return array<int, KoboDevice>
     */
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
