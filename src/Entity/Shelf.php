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
     * @var Collection<int, KoboDevice>
     */
    #[ORM\ManyToMany(targetEntity: KoboDevice::class, mappedBy: 'shelves')]
    private Collection $koboDevices;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $queryString = null;
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $queryFilter = null;
    #[ORM\Column(type: Types::STRING, nullable: true)]
    private ?string $queryOrder = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private \DateTimeInterface $created;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTimeInterface $updated = null;

    #[ORM\Column(type: Types::STRING, length: 36, unique: true, nullable: true)]
    private ?string $uuid = null;

    public function __construct()
    {
        $this->books = new ArrayCollection();
        $this->koboDevices = new ArrayCollection();
        $this->uuid = $this->generateUuid();
        $this->created = new \DateTimeImmutable();
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

    public function isDynamic(): bool
    {
        return $this->queryString !== null;
    }

    public function getQueryString(): ?string
    {
        return $this->queryString;
    }

    public function setQueryString(?string $queryString): static
    {
        $this->queryString = $queryString;

        return $this;
    }

    /**
     * @param Collection<int, KoboDevice> $collection
     */
    public function setKoboDevices(Collection $collection): self
    {
        $this->koboDevices = $collection;

        return $this;
    }

    public function addKoboDevice(KoboDevice $koboDevice): self
    {
        if (!$this->koboDevices->contains($koboDevice)) {
            $this->koboDevices->add($koboDevice);
            $koboDevice->addShelf($this);
        }

        return $this;
    }

    public function removeKoboDevice(KoboDevice $koboDevice): self
    {
        if ($this->koboDevices->contains($koboDevice)) {
            $this->koboDevices->removeElement($koboDevice);
            $koboDevice->removeShelf($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, KoboDevice>
     */
    public function getKoboDevices(): Collection
    {
        return $this->koboDevices;
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

    public function setCreated(\DateTimeInterface $created): void
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

    public function getQueryFilter(): ?string
    {
        return $this->queryFilter;
    }

    public function setQueryFilter(?string $queryFilter): void
    {
        $this->queryFilter = $queryFilter;
    }

    public function getQueryOrder(): ?string
    {
        return $this->queryOrder;
    }

    public function setQueryOrder(?string $queryOrder): void
    {
        $this->queryOrder = $queryOrder;
    }
}
