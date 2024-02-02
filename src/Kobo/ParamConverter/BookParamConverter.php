<?php

namespace App\Kobo\ParamConverter;

use App\Entity\Book;
use App\Repository\BookRepository;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;

#[AutoconfigureTag('controller.argument_value_resolver', ['priority' => 150])]
class BookParamConverter implements ValueResolverInterface
{
    public function __construct(protected BookRepository $bookRepository)
    {
    }

    public function supports(Request $request, ArgumentMetadata $configuration): bool
    {
        return $configuration->getType() === Book::class
            && $this->getUuid($request) !== null;
    }

    public function apply(Request $request): ?Book
    {
        return $this->bookRepository->findOneBy(['uuid' => $this->getUuid($request)]);
    }

    /**
     * @return array<int, Book>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if ($this->supports($request, $argument) === false) {
            return [];
        }

        return array_filter([$this->apply($request)]);
    }

    private function getUuid(Request $request): ?string
    {
        /** @var array<string, string|int> $params */
        $params = $request->attributes->get('_route_params', []);

        return array_key_exists('uuid', $params) ? ((string) $params['uuid']) : null;
    }
}
