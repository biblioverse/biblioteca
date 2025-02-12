<?php

namespace App\Entity;

use App\Enum\AgeCategory;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank()]
    #[ORM\Column(length: 180, unique: true, nullable: true, options: ['default' => null])]
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

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $birthday = null;

    #[ORM\Column(nullable: true)]
    private ?AgeCategory $maxAgeCategory = null;

    /**
     * @deprecated use global prompts
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $bookSummaryPrompt = null;

    /**
     * @deprecated use global prompts
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $bookKeywordPrompt = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $theme = null;

    /**
     * @var Collection<int, KoboDevice>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: KoboDevice::class, orphanRemoval: true)]
    private Collection $kobos;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $lastLogin = null;

    #[ORM\Column(length: 2, nullable: false, options: ['default' => 'en'])]
    private string $language = 'en';

    #[ORM\Column]
    private bool $useKoboDevices = true;

    /**
     * @var Collection<int, BookmarkUser>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: BookmarkUser::class, orphanRemoval: true)]
    private Collection $bookmarkUsers;

    /**
     * @var Collection<int, OpdsAccess>
     */
    #[ORM\OneToMany(mappedBy: 'user', targetEntity: OpdsAccess::class, orphanRemoval: true)]
    private Collection $opdsAccesses;

    public function __construct()
    {
        $this->bookInteractions = new ArrayCollection();
        $this->shelves = new ArrayCollection();
        $this->kobos = new ArrayCollection();
        $this->bookmarkUsers = new ArrayCollection();
        $this->opdsAccesses = new ArrayCollection();
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
     * @return non-empty-string
     */
    #[\Override]
    public function getUserIdentifier(): string
    {
        if ($this->username === null) {
            throw new \LogicException('The user identifier cannot be null');
        }
        if ($this->username === '') {
            throw new \LogicException('The user identifier cannot be empty');
        }

        return $this->username;
    }

    /**
     * @see UserInterface
     */
    #[\Override]
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function addRole(string $role): self
    {
        $this->roles[] = $role;
        $this->roles = array_unique($this->roles);

        return $this;
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
    #[\Override]
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
    #[\Override]
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
        $this->bookInteractions->removeElement($bookInteraction);

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

    public function getBirthday(): ?\DateTimeImmutable
    {
        return $this->birthday;
    }

    public function setBirthday(?\DateTimeImmutable $birthday): static
    {
        $this->birthday = $birthday;

        return $this;
    }

    public function getMaxAgeCategory(): ?AgeCategory
    {
        return $this->maxAgeCategory;
    }

    public function setMaxAgeCategory(?AgeCategory $maxAgeCategory): static
    {
        $this->maxAgeCategory = $maxAgeCategory;

        return $this;
    }

    /**
     * @deprecated use global prompts
     */
    public function getBookSummaryPrompt(): ?string
    {
        return $this->bookSummaryPrompt;
    }

    /**
     * @deprecated use global prompts
     */
    public function setBookSummaryPrompt(?string $bookSummaryPrompt): static
    {
        $this->bookSummaryPrompt = $bookSummaryPrompt;

        return $this;
    }

    /**
     * @deprecated use global prompts
     */
    public function getBookKeywordPrompt(): ?string
    {
        return $this->bookKeywordPrompt;
    }

    /**
     * @deprecated use global prompts
     */
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
     * @return Collection<int, KoboDevice>
     */
    public function getKobos(): Collection
    {
        return $this->kobos;
    }

    public function getLastLogin(): ?\DateTimeImmutable
    {
        return $this->lastLogin;
    }

    public function setLastLogin(?\DateTimeImmutable $lastLogin): static
    {
        $this->lastLogin = $lastLogin;

        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(string $language): static
    {
        $this->language = $language;

        return $this;
    }

    public function isUseKoboDevices(): bool
    {
        return $this->useKoboDevices;
    }

    public function setUseKoboDevices(bool $useKoboDevices): static
    {
        $this->useKoboDevices = $useKoboDevices;

        return $this;
    }

    /**
     * @return Collection<int, BookmarkUser>
     */
    public function getBookmarkUsers(): Collection
    {
        return $this->bookmarkUsers;
    }

    public function addBookmarkUser(BookmarkUser $bookmarkUser): static
    {
        if (!$this->bookmarkUsers->contains($bookmarkUser)) {
            $this->bookmarkUsers->add($bookmarkUser);
            $bookmarkUser->setUser($this);
        }

        return $this;
    }

    public function removeBookmarkUser(BookmarkUser $bookmarkUser): static
    {
        // set the owning side to null (unless already changed)
        if ($this->bookmarkUsers->removeElement($bookmarkUser) && $bookmarkUser->getUser() === $this) {
            $bookmarkUser->setUser(null);
        }

        return $this;
    }

    public function getBookmarkForBook(Book $book): ?BookmarkUser
    {
        foreach ($this->bookmarkUsers as $bookmarkUser) {
            if ($bookmarkUser->getBook() === $book) {
                return $bookmarkUser;
            }
        }

        return null;
    }

    public function removeBookmarkForBook(Book $book): self
    {
        $this->getBookmarkForBook($book)?->setUser(null)->setBook(null);

        return $this;
    }

    /**
     * @return Collection<int, OpdsAccess>
     */
    public function getOpdsAccesses(): Collection
    {
        return $this->opdsAccesses;
    }

    public function addOpdsAccess(OpdsAccess $opdsAccess): static
    {
        if (!$this->opdsAccesses->contains($opdsAccess)) {
            $this->opdsAccesses->add($opdsAccess);
            $opdsAccess->setUser($this);
        }

        return $this;
    }

    public function removeOpdsAccess(OpdsAccess $opdsAccess): static
    {
        // set the owning side to null (unless already changed)
        if ($this->opdsAccesses->removeElement($opdsAccess) && $opdsAccess->getUser() === $this) {
            $opdsAccess->setUser(null);
        }

        return $this;
    }
}
