<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const AGE_CATEGORIES = [
        'E (Everyone)' => '1',
        'E10+ (10 and more)' => '2',
        'T (13 and more)' => '3',
        'M (17 and more)' => '4',
        'A (Adults only)' => '5',
    ];
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    private ?string $username = null;

    /**
     * @var array<string>
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private string $password;

    /**
     * @var Collection<int, BookInteraction>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: BookInteraction::class, orphanRemoval: true)]
    private Collection $bookInteractions;

    /**
     * @var Collection<int, Shelf>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Shelf::class, orphanRemoval: true)]
    private Collection $shelves;

    #[ORM\Column(options: ['default' => true])]
    private bool $displaySeries = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $displayAuthors = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $displayTags = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $displayPublishers = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $displayTimeline = true;

    #[ORM\Column(options: ['default' => true])]
    private bool $displayAllBooks = true;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $birthday = null;

    #[ORM\Column(nullable: true)]
    private ?int $maxAgeCategory = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $openAIKey = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $bookSummaryPrompt = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $bookKeywordPrompt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $theme = null;

    /**
     * @var Collection<int, Kobo>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Kobo::class, orphanRemoval: true)]
    private Collection $kobos;
    public function __construct()
    {
        $this->bookInteractions = new ArrayCollection();
        $this->shelves = new ArrayCollection();
        $this->kobos = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param array<string> $roles
     *
     * @return $this
     */
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return Collection<int, BookInteraction>
     */
    public function getBookInteractions(): Collection
    {
        return $this->bookInteractions;
    }

    public function addBookInteraction(BookInteraction $bookInteraction): static
    {
        if (!$this->bookInteractions->contains($bookInteraction)) {
            $this->bookInteractions->add($bookInteraction);
            $bookInteraction->setUser($this);
        }

        return $this;
    }

    public function removeBookInteraction(BookInteraction $bookInteraction): static
    {
        if ($this->bookInteractions->removeElement($bookInteraction)) {
            // set the owning side to null (unless already changed)
            if ($bookInteraction->getUser() === $this) {
                $bookInteraction->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Shelf>
     */
    public function getShelves(): Collection
    {
        return $this->shelves;
    }

    public function addShelf(Shelf $shelf): static
    {
        if (!$this->shelves->contains($shelf)) {
            $this->shelves->add($shelf);
            $shelf->setUser($this);
        }

        return $this;
    }

    public function removeShelf(Shelf $shelf): static
    {
        $this->shelves->removeElement($shelf);

        return $this;
    }

    public function isDisplaySeries(): bool
    {
        return $this->displaySeries;
    }

    public function setDisplaySeries(bool $displaySeries): static
    {
        $this->displaySeries = $displaySeries;

        return $this;
    }

    public function isDisplayAuthors(): bool
    {
        return $this->displayAuthors;
    }

    public function setDisplayAuthors(bool $displayAuthors): static
    {
        $this->displayAuthors = $displayAuthors;

        return $this;
    }

    public function isDisplayTags(): bool
    {
        return $this->displayTags;
    }

    public function setDisplayTags(bool $displayTags): static
    {
        $this->displayTags = $displayTags;

        return $this;
    }

    public function isDisplayPublishers(): bool
    {
        return $this->displayPublishers;
    }

    public function setDisplayPublishers(bool $displayPublishers): static
    {
        $this->displayPublishers = $displayPublishers;

        return $this;
    }

    public function isDisplayTimeline(): bool
    {
        return $this->displayTimeline;
    }

    public function setDisplayTimeline(bool $displayTimeline): static
    {
        $this->displayTimeline = $displayTimeline;

        return $this;
    }

    public function isDisplayAllBooks(): bool
    {
        return $this->displayAllBooks;
    }

    public function setDisplayAllBooks(bool $displayAllBooks): static
    {
        $this->displayAllBooks = $displayAllBooks;

        return $this;
    }

    public function getBirthday(): ?\DateTimeInterface
    {
        return $this->birthday;
    }

    public function setBirthday(?\DateTimeInterface $birthday): static
    {
        $this->birthday = $birthday;

        return $this;
    }

    public function getMaxAgeCategory(): ?int
    {
        return $this->maxAgeCategory;
    }

    public function setMaxAgeCategory(?int $maxAgeCategory): static
    {
        $this->maxAgeCategory = $maxAgeCategory;

        return $this;
    }

    public function getOpenAIKey(): ?string
    {
        return $this->openAIKey;
    }

    public function setOpenAIKey(?string $openAIKey): static
    {
        $this->openAIKey = $openAIKey;

        return $this;
    }

    public function getBookSummaryPrompt(): ?string
    {
        return $this->bookSummaryPrompt;
    }

    public function setBookSummaryPrompt(?string $bookSummaryPrompt): static
    {
        $this->bookSummaryPrompt = $bookSummaryPrompt;

        return $this;
    }

    public function getBookKeywordPrompt(): ?string
    {
        return $this->bookKeywordPrompt;
    }

    public function setBookKeywordPrompt(?string $bookKeywordPrompt): static
    {
        $this->bookKeywordPrompt = $bookKeywordPrompt;

        return $this;
    }

    public function getTheme(): ?string
    {
        return $this->theme;
    }

    public function setTheme(?string $theme): static
    {
        $this->theme = $theme;

        return $this;
    }

    /**
     * @return Collection<int,Kobo>
     */
    public function getKobos(): Collection
    {
        return $this->kobos;
    }
}
