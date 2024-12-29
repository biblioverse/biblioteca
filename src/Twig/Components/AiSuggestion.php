<?php

namespace App\Twig\Components;

use App\Ai\AiCommunicatorInterface;
use App\Ai\CommunicatorDefiner;
use App\Ai\Context\ContextBuilder;
use App\Ai\Prompt\SummaryPrompt;
use App\Ai\Prompt\TagPrompt;
use App\Entity\Book;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Symfony\UX\TwigComponent\Attribute\PostMount;

#[AsLiveComponent]
final class AiSuggestion
{
    use DefaultActionTrait;

    #[LiveProp(writable: ['title', 'serie', 'serieIndex', 'publisher', 'verified', 'summary', 'authors', 'tags', 'ageCategory'])]
    public Book $book;

    #[LiveProp()]
    public string $field;

    public User $user;

    #[LiveProp(writable: true)]
    public string $prompt = '';

    /** @var array<string> */
    public array $result = [];
    public array $suggestions = [];

    public const array EMPTY_SUGGESTIONS = [
        'image' => [],
        'title' => [],
        'authors' => [],
        'publisher' => [],
        'tags' => [],
        'summary' => [],
    ];

    public function __construct(
        private Security $security,
        private CommunicatorDefiner $aiCommunicator,
        private ContextBuilder $contextBuilder,
    ) {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \LogicException('User must be logged in');
        }
        $this->user = $user;
    }

    #[PostMount]
    public function postMount(): void
    {
        $this->prompt = match ($this->field) {
            'summary' => (new SummaryPrompt($this->book, $this->user))->getPrompt(),
            'tags' => (new TagPrompt($this->book, $this->user))->getPrompt(),
            default => throw new \InvalidArgumentException('Invalid field'),
        };
    }

    #[LiveAction]
    public function generate(): void
    {
        $this->suggestions = self::EMPTY_SUGGESTIONS;

        $communicator = $this->aiCommunicator->getCommunicator();

        if (!$communicator instanceof AiCommunicatorInterface) {
            return;
        }

        $promptObj = match ($this->field) {
            'summary' => new SummaryPrompt($this->book, $this->user),
            'tags' => new TagPrompt($this->book, $this->user),
            default => throw new \InvalidArgumentException('Invalid field'),
        };

        $promptObj = $this->contextBuilder->getContext($promptObj);

        $promptObj->setPrompt($this->prompt);

        $result = $communicator->interrogate($promptObj);

        $this->result = is_array($result) ? $result : [$result];

        $this->suggestions[$this->field] = $this->result;
    }
}
