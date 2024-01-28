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

    public function supports(Request $request, ArgumentMetadata $argument): bool
    {
        return $argument->getType() === Book::class
            && $this->getFieldValue($request, $argument) !== null;
    }

    public function apply(Request $request, ArgumentMetadata $argument): ?Book
    {
        return $this->bookRepository->findOneBy([$this->getFieldName($argument) => $this->getFieldValue($request, $argument)]);
    }

    /**
     * @return array<int, Book>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if ($this->supports($request, $argument) === false) {
            return [];
        }

        return array_filter([$this->apply($request, $argument)]);
    }

    private function getFieldValue(Request $request, ArgumentMetadata $argument): ?string
    {
        /** @var array<string, string|int> $params */
        $params = $request->attributes->get('_route_params', []);
        $name = $this->getFieldName($argument);

        return array_key_exists($name, $params) ? ((string) $params[$name]) : null;
    }

    private function getFieldName(ArgumentMetadata $argument): string
    {
        return match ($argument->getName()) {
            'bookId' => 'id',
            default => 'uuid',
        };
    }
}
