<?php

namespace App\Entity;

use App\Repository\LibraryFolderRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: LibraryFolderRepository::class)]
class LibraryFolder implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $name;

    #[ORM\Column(length: 255)]
    private string $folder;

    #[ORM\Column(length: 128, unique: true)]
    #[Gedmo\Slug(fields: ['name'], style: 'lower')]
    private string $slug;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $icon = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $folderNamingFormat = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fileNamingFormat = null;

    /**
     * @var Collection<int, Book>
     */
    #[ORM\OneToMany(targetEntity: Book::class, mappedBy: 'libraryFolder')]
    private Collection $books;

    #[ORM\Column]
    private bool $defaultLibrary;

    #[ORM\Column]
    private bool $autoRelocation;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'libraryFolders')]
    private Collection $allowedUsers;

    #[ORM\Column(length: 255)]
    private string $volumeIdentifier;

    public function __construct()
    {
        $this->books = new ArrayCollection();
        $this->allowedUsers = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getFolder(): string
    {
        return $this->folder;
    }

    public function setFolder(string $folder): static
    {
        $this->folder = $folder;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(?string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function getFolderNamingFormat(): ?string
    {
        return $this->folderNamingFormat;
    }

    public function setFolderNamingFormat(?string $folderNamingFormat): static
    {
        $this->folderNamingFormat = $folderNamingFormat;

        return $this;
    }

    public function getFileNamingFormat(): ?string
    {
        return $this->fileNamingFormat;
    }

    public function setFileNamingFormat(?string $fileNamingFormat): static
    {
        $this->fileNamingFormat = $fileNamingFormat;

        return $this;
    }

    /**
     * @return Collection<int, Book>
     */
    public function getBooks(): Collection
    {
        return $this->books;
    }

    public function addBook(Book $book): static
    {
        if (!$this->books->contains($book)) {
            $this->books->add($book);
            $book->setLibraryFolder($this);
        }

        return $this;
    }

    public function removeBook(Book $book): static
    {
        // set the owning side to null (unless already changed)
        if ($this->books->removeElement($book) && $book->getLibraryFolder() === $this) {
            $book->setLibraryFolder(null);
        }

        return $this;
    }

    public function isDefaultLibrary(): bool
    {
        return $this->defaultLibrary;
    }

    public function setDefaultLibrary(bool $defaultLibrary): static
    {
        $this->defaultLibrary = $defaultLibrary;

        return $this;
    }

    public function isAutoRelocation(): bool
    {
        return $this->autoRelocation;
    }

    public function setAutoRelocation(bool $autoRelocation): static
    {
        $this->autoRelocation = $autoRelocation;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getAllowedUsers(): Collection
    {
        return $this->allowedUsers;
    }

    public function addAllowedUser(User $allowedUser): static
    {
        if (!$this->allowedUsers->contains($allowedUser)) {
            $this->allowedUsers->add($allowedUser);
        }

        return $this;
    }

    public function removeAllowedUser(User $allowedUser): static
    {
        $this->allowedUsers->removeElement($allowedUser);

        return $this;
    }

    public function getVolumeIdentifier(): string
    {
        return $this->volumeIdentifier;
    }

    public function setVolumeIdentifier(string $volumeIdentifier): static
    {
        $this->volumeIdentifier = $volumeIdentifier;

        return $this;
    }

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }
}
