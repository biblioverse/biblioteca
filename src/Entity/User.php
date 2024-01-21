<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
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

    #[ORM\Column(options: ["default" => true])]
    private bool $displaySeries = true;

    #[ORM\Column(options: ["default" => true])]
    private bool $displayAuthors = true;

    #[ORM\Column(options: ["default" => true])]
    private bool $displayTags = true;

    #[ORM\Column(options: ["default" => true])]
    private bool $displayPublishers = true;

    #[ORM\Column(options: ["default" => true])]
    private bool $displayTimeline = true;

    #[ORM\Column(options: ["default" => true])]
    private bool $displayAllBooks = true;

    public function __construct()
    {
        $this->bookInteractions = new ArrayCollection();
        $this->shelves = new ArrayCollection();
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
}
