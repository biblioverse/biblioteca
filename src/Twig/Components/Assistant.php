<?php

namespace App\Twig\Components;

use App\Ai\Communicator\AiAction;
use App\Ai\Communicator\AiChatInterface;
use App\Ai\Communicator\CommunicatorDefiner;
use App\Ai\Message;
use App\Entity\Book;
use App\Entity\User;
use App\Enum\AiMessageRole;
use App\Form\BookType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Intl\Locales;
use Symfony\Component\Routing\RouterInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
final class Assistant extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;
    use ComponentToolsTrait;

    #[LiveProp(fieldName: 'formData')]
    public Book $book;

    #[LiveProp(writable: true)]
    public string $message = '';

    public bool $isProcessing = false;

    /**
     * @var Message[]
     */
    #[LiveProp(useSerializerForHydration: true)]
    public array $messages = [];

    public function __construct(
        private readonly CommunicatorDefiner $communicatorDefiner,
        private readonly RouterInterface $router,
    ) {
    }

    protected function instantiateForm(): FormInterface
    {
        // we can extend AbstractController to get the normal shortcuts
        $form = $this->createForm(BookType::class, $this->book);

        if ($this->messages === []) {
            $initialMessage = new Message('
You are a librarian expert in retrieving information about books. Currently we are talking about '.$this->getBookString($this->book).'
The user may ask you questions about the book, you can answer how you need but 
- if the user asks for a summary, you should provide a markdown formatted json with only the "summary" key in addition.
- if the user asks for genres, categories or theme, you should provide a markdown formatted json with only the "tags" key in addition.
- if the user asks about the authors you should provide a markdown formatted json with only the "authors" key in addition Try to provide the author\'s full name if possible and only if you are sure of it.
feel free to ask for more information if needed.
If you don\'t know the answer to the user question, mention it in your answer.
', AiMessageRole::System);
            $this->messages[] = $initialMessage;
        }

        return $form;
    }

    #[LiveAction]
    public function sendMessage(): void
    {
        $this->isProcessing = true;
        $this->messages[] = new Message($this->message, AiMessageRole::User);
        $this->message = '';

        $communicator = $this->communicatorDefiner->getCommunicator(AiAction::Assistant);

        if (!$communicator instanceof AiChatInterface) {
            throw new \RuntimeException('not chat');
        }
        $answer = $communicator->chat($this->messages);

        $message = new Message($answer, AiMessageRole::Assistant);

        $convert = $this->getMarkdownJson($answer);

        if ($convert !== null) {
            $message->suggestions = $convert['suggestions'];
            $message->text = $convert['text'];
        }

        $this->messages[] = $message;

        $this->isProcessing = false;
    }

    #[LiveAction]
    public function save(Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        // Submit the form! If validation fails, an exception is thrown
        // and the component is automatically re-rendered with the errors
        $this->submitForm();

        /** @var Book $book */
        $book = $this->getForm()->getData();
        $entityManager->persist($book);
        $entityManager->flush();

        $this->addFlash('success', 'Book saved!');

        return $this->redirect($request->headers->get('referer') ?? $this->router->generate('app_book', [
            'book' => $book->getId(),
            'slug' => $book->getSlug(),
        ]));
    }

    #[LiveAction]
    public function generate(#[LiveArg] string $field): void
    {
        $language = 'english';
        $user = $this->getUser();
        if ($user instanceof User) {
            $language = $user->getLanguage();
            $names = Locales::getNames();
            $language = $names[$language] ?? $language;
        }
        $this->message = 'Can you generate a '.$field.' for me in '.$language.'?';
        $this->sendMessage();
    }

    #[LiveAction]
    public function acceptSuggestion(#[LiveArg] string $field, #[LiveArg] array|string $suggestion): void
    {
        if (is_array($suggestion)) {
            $this->formValues[$field.'String'] = implode(',', $suggestion);
        } else {
            $this->formValues[$field] = $suggestion;
        }
        $this->dispatchBrowserEvent('select:clear', ['field' => $field, 'book' => $this->book->getId()]);
    }

    private function getMarkdownJson(string $text): ?array
    {
        // Check for markdown code block with json
        if (preg_match('/```json\s*({[\s\S]*?})\s*```/', $text, $matches) >= 1) {
            // Validate JSON
            try {
                $suggestions = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
            } catch (\JsonException) {
                return null;
            }
            $text = preg_replace('/```json\s*({[\s\S]*?})\s*```/', '', $text, 1);

            return ['suggestions' => $suggestions, 'text' => $text];
        }

        return null;
    }

    public function getBookString(Book $book): string
    {
        $title = $book->getTitle();
        if (preg_match('/T\d+/', $title) === false || preg_match('/T\d+/', $title) === 0) {
            $bookString = '"'.$book->getTitle().'" by '.implode(' and ', $book->getAuthors());
        } else {
            $bookString = 'a book by '.implode(' and ', $book->getAuthors());
        }

        if ($book->getSerie() !== null) {
            $bookString .= ' number '.$book->getSerieIndex().' in the series "'.$book->getSerie().'"';
        }

        return $bookString;
    }
}
