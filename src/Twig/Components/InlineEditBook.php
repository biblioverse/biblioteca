<?php

namespace App\Twig\Components;

use App\Entity\Book;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Intl\Locales;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\LiveComponent\ValidatableComponentTrait;

#[AsLiveComponent(method: 'get')]
class InlineEditBook extends AbstractController
{
    use DefaultActionTrait;
    use ValidatableComponentTrait;
    use ComponentToolsTrait;

    #[LiveProp(writable: ['title', 'serie', 'serieIndex', 'publisher', 'verified', 'summary', 'authors', 'tags', 'ageCategory', 'pageNumber', 'language'])]
    public Book $book;

    #[LiveProp()]
    public bool $isEditing = false;

    public bool $displayOriginal = true;

    /**
     * @var array<string, array<string, string>>
     */
    #[LiveProp()]
    public array $suggestions = [];

    #[LiveProp()]
    public string $field;

    /**
     * @var array<string,string> list of locales indexed by 2 letters code
     */
    public array $locales;

    public ?string $flashMessage = null;

    public function __construct()
    {
        $this->locales = $this->getTwoLettersLocales();
    }

    #[LiveAction]
    public function activateEditing(): void
    {
        $this->isEditing = true;
    }

    #[LiveAction]
    public function usesuggestion(#[LiveArg] string $field, #[LiveArg] string $suggestion, EntityManagerInterface $entityManager): void
    {
        $this->isEditing = true;
        $to_call = 'set'.ucfirst($field);
        $value = $suggestion === 'all' ? $this->suggestions[$field] : $this->suggestions[$field][$suggestion];
        if (is_callable([$this->book, $to_call])) {
            if ('tags' === $field) {
                if (is_array($value)) {
                    foreach ($value as $tag) {
                        $this->book->addTag($tag);
                    }
                } else {
                    $this->book->addTag($value);
                }
            } elseif ('authors' === $field) {
                if (is_array($value)) {
                    foreach ($value as $tag) {
                        $this->book->addAuthor($tag);
                    }
                } else {
                    $this->book->addAuthor($value);
                }
            } else {
                /* @phpstan-ignore-next-line */
                $this->book->$to_call($value);
            }
        }
        $entityManager->flush();
        $this->dispatchBrowserEvent('manager:flush');
        $this->isEditing = false;

        $this->flashMessage = ' book updated';
    }

    /**
     * @throws \JsonException
     */
    #[LiveAction]
    #[LiveListener('submit')]
    public function save(Request $request, EntityManagerInterface $entityManager): void
    {
        $all = $request->request->all();
        if (!array_key_exists('data', $all)) {
            return;
        }
        if (!is_string($all['data'])) {
            return;
        }
        $data = json_decode($all['data'], true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            return;
        }
        if (array_key_exists('updated', $data) && is_array($data['updated']) && array_key_exists('book.serieIndex', $data['updated']) && '' === $data['updated']['book.serieIndex']) {
            $this->book->setSerieIndex(null);
        }

        $entityManager->flush();
        $this->dispatchBrowserEvent('manager:flush');
        $this->isEditing = false;

        $this->flashMessage = ' book updated';
    }

    /**
     * @return array<string,string> list of languages indexed by their 2-letter locale code
     */
    private function getTwoLettersLocales(): array
    {
        // Currently only 2-letter locales are supported in DB field.
        return array_filter(Locales::getNames(), fn (string $locale) => strlen($locale) === 2, ARRAY_FILTER_USE_KEY);
    }
}
