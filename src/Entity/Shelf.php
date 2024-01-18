<?php

namespace App\Entity;

use App\Repository\ShelfRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: ShelfRepository::class)]
class Shelf
{
    use UuidGeneratorTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private int $id;

    #[ORM\ManyToOne(inversedBy: 'shelves')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    /**
     * @var Collection<int, Book>
     */
    #[ORM\ManyToMany(targetEntity: Book::class, inversedBy: 'shelves')]
    private Collection $books;

    #[ORM\Column(length: 255, nullable: false)]
    private string $name;

    #[ORM\Column(length: 128, unique: true, nullable: false)]
    #[Gedmo\Slug(fields: ['name', 'id'], style: 'lower')]
    private string $slug;

    /**
     * @var Collection<int, Kobo>
     */
    #[ORM\ManyToMany(targetEntity: Kobo::class, mappedBy: 'shelves')]
    private Collection $kobos;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $queryString = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private ?\DateTimeInterface $created = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTimeInterface $updated = null;

    #[ORM\Column(type: Types::STRING, length: 36, unique: true, nullable: true)]
    private ?string $uuid = null;

    public function __construct()
    {
        $this->books = new ArrayCollection();
        $this->uuid = $this->generateUuid();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): static
    {
        $this->user = $user;

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
            $book->addShelf($this);
            $this->books->add($book);
        }

        return $this;
    }

    public function removeBook(Book $book): static
    {
        $this->books->removeElement($book);

        return $this;
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

    public function getSlug(): string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getQueryString(): ?array
    {
        return $this->queryString;
    }

    public function setQueryString(?array $queryString): static
    {
        $this->queryString = $queryString;

        return $this;
    }

    /**
     * @param Collection<int, Kobo> $collection
     */
    public function setKobos(Collection $collection): self
    {
        $this->kobos = $collection;

        return $this;
    }

    public function addKobo(Kobo $kobo): self
    {
        if (!$this->kobos->contains($kobo)) {
            $this->kobos->add($kobo);
        }

        return $this;
    }

    public function removeKobo(Kobo $kobo): self
    {
        if (!$this->kobos->contains($kobo)) {
            $this->kobos->add($kobo);
        }

        return $this;
    }

    /**
     * @return Collection<int, Kobo>
     */
    public function getKobos(): Collection
    {
        return $this->kobos;
    }

    public function getUpdated(): ?\DateTimeInterface
    {
        return $this->updated;
    }

    public function setUpdated(?\DateTimeInterface $updated): void
    {
        $this->updated = $updated;
    }

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(?\DateTimeInterface $created): void
    {
        $this->created = $created;
    }

    public function getUuid(): ?string
    {
        if ($this->uuid === null) {
            $this->uuid = $this->generateUuid();
        }

        return $this->uuid;
    }

    public function setUuid(?string $uuid): void
    {
        $this->uuid = $uuid;
    }
}
