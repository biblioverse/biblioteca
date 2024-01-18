<?php

namespace App\Entity;

use App\Repository\BookRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: BookRepository::class)]
class Book
{
    use UuidGeneratorTrait;
    public const UCWORDS_SEPARATORS = " \t\r\n\f\v-.'";
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 36, unique: true, nullable: true)]
    private ?string $uuid = null;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(length: 255, unique: true)]
    private string $checksum;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $summary = null;

    #[ORM\Column(length: 128, unique: true)]
    #[Gedmo\Slug(fields: ['title', 'id'], style: 'lower')]
    private string $slug;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private \DateTimeInterface $created;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTimeInterface $updated;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imagePath = null;
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $imageFilename = null;

    #[ORM\Column(length: 255)]
    private string $bookPath;
    #[ORM\Column(length: 255)]
    private string $bookFilename;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $serie = null;

    #[ORM\Column(nullable: true)]
    private ?float $serieIndex = null;

    #[ORM\Column(length: 2, nullable: true)]
    private ?string $language = null;

    #[ORM\Column(nullable: true)]
    private ?int $pageNumber = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $publisher = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $publishDate = null;

    /**
     * @var array<string>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $authors = [];

    #[ORM\Column(length: 5)]
    private string $extension;

    #[ORM\Column(length: 5, nullable: true)]
    private ?string $imageExtension = null;

    /**
     * @var Collection<int, BookInteraction>
     */
    #[ORM\OneToMany(mappedBy: 'book', targetEntity: BookInteraction::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $bookInteractions;

    /**
     * @var array<string>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $tags = null;

    #[ORM\Column(nullable: false)]
    private bool $verified = false;

    /**
     * @var Collection<int, Shelf>
     */
    #[ORM\ManyToMany(targetEntity: Shelf::class, mappedBy: 'books', cascade: ['persist'])]
    private Collection $shelves;

    #[ORM\Column(nullable: true)]
    private ?int $ageCategory = null;

    /**
     * @var Collection<int, KoboSyncedBook>
     */
    #[ORM\OneToMany(mappedBy: 'book', targetEntity: KoboSyncedBook::class, cascade: ['remove'], orphanRemoval: true)]
    private Collection $koboSyncedBooks;

    public function __construct()
    {
        $this->bookInteractions = new ArrayCollection();
        $this->shelves = new ArrayCollection();
        $this->uuid = $this->generateUuid();
        $this->koboSyncedBooks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = trim($title);

        if ('' === $title) {
            $this->title = 'unknown';
        }

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

    public function getCreated(): \DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(\DateTimeInterface $created): void
    {
        $this->created = $created;
    }

    public function getUpdated(): ?\DateTimeInterface
    {
        return $this->updated;
    }

    public function setUpdated(?\DateTimeInterface $updated): void
    {
        $this->updated = $updated;
    }

    public function getChecksum(): string
    {
        return $this->checksum;
    }

    public function setChecksum(string $checksum): void
    {
        $this->checksum = $checksum;
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function setSummary(?string $summary): void
    {
        $this->summary = $summary;
    }

    public function getImagePath(): ?string
    {
        return $this->imagePath;
    }

    public function setImagePath(?string $imagePath): void
    {
        $this->imagePath = $imagePath;
    }

    public function getImageFilename(): ?string
    {
        return $this->imageFilename;
    }

    public function setImageFilename(?string $imageFilename): void
    {
        $this->imageFilename = $imageFilename;
    }

    public function getBookPath(): string
    {
        return $this->bookPath;
    }

    public function setBookPath(string $bookPath): void
    {
        $this->bookPath = $bookPath;
    }

    public function getBookFilename(): string
    {
        return $this->bookFilename;
    }

    public function setBookFilename(string $bookFilename): void
    {
        $this->bookFilename = $bookFilename;
    }

    public function getSerie(): ?string
    {
        return $this->serie;
    }

    public function setSerie(?string $serie): static
    {
        $serie = trim($serie ?? '');
        if ('' === $serie) {
            $serie = null;
        }
        $this->serie = $serie;

        return $this;
    }

    public function getSerieIndex(): ?float
    {
        return $this->serieIndex;
    }

    public function setSerieIndex(?float $serieIndex): static
    {
        $this->serieIndex = $serieIndex;

        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): static
    {
        $this->language = $language;

        return $this;
    }

    public function getPublisher(): ?string
    {
        return $this->publisher;
    }

    public function setPublisher(?string $publisher): static
    {
        $this->publisher = $publisher;

        return $this;
    }

    public function getPublishDate(): ?\DateTimeInterface
    {
        return $this->publishDate;
    }

    public function setPublishDate(?\DateTimeInterface $publishDate): static
    {
        $this->publishDate = $publishDate;

        return $this;
    }

    /**
     * @return array<string>
     */
    public function getAuthors(): array
    {
        return array_map(static function ($item) {
            return ucwords(strtolower($item), self::UCWORDS_SEPARATORS);
        }, $this->authors);
    }

    /**
     * @param array<string> $authors
     *
     * @return $this
     */
    public function setAuthors(array $authors): static
    {
        $this->authors = $authors;

        return $this;
    }

    public function addAuthor(string $author): static
    {
        if (!in_array($author, $this->authors, true) && '' !== $author) {
            $this->authors[] = ucwords(strtolower($author), self::UCWORDS_SEPARATORS);
        }
        $this->authors = array_values($this->authors);

        return $this;
    }

    public function removeAuthor(string $author): static
    {
        foreach ($this->authors as $key => $value) {
            if (strtolower($value) === strtolower($author)) {
                unset($this->authors[$key]);
            }
        }

        $this->authors = array_values($this->authors);

        return $this;
    }

    public function addTag(string $tag): static
    {
        $tag = ucwords(strtolower($tag), self::UCWORDS_SEPARATORS);
        if (null === $this->tags) {
            $this->tags = [];
        }

        if ('' !== $tag && !in_array($tag, $this->tags, true)) {
            $this->tags[] = $tag;
        }
        $this->tags = array_values($this->tags);

        return $this;
    }

    public function removeTag(string $tag): static
    {
        if (null === $this->tags) {
            $this->tags = [];
        }

        foreach ($this->tags as $key => $value) {
            if (strtolower($value) === strtolower($tag)) {
                unset($this->tags[$key]);
            }
        }

        $this->tags = array_values($this->tags);

        return $this;
    }

    public function getExtension(): string
    {
        return $this->extension;
    }

    public function setExtension(string $extension): static
    {
        $this->extension = $extension;

        return $this;
    }

    public function getImageExtension(): ?string
    {
        return $this->imageExtension;
    }

    public function setImageExtension(string $imageExtension): void
    {
        $this->imageExtension = $imageExtension;
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
            $bookInteraction->setBook($this);
        }

        return $this;
    }

    public function removeBookInteraction(BookInteraction $bookInteraction): static
    {
        if ($this->bookInteractions->removeElement($bookInteraction)) {
            // set the owning side to null (unless already changed)
            if ($bookInteraction->getBook() === $this) {
                $bookInteraction->setBook(null);
            }
        }

        return $this;
    }

    /**
     * @return array<string>|null
     */
    public function getTags(): ?array
    {
        return $this->tags;
    }

    /**
     * @param array<string>|null $tags
     *
     * @return $this
     */
    public function setTags(?array $tags): static
    {
        $this->tags = $tags;

        return $this;
    }

    public function isVerified(): bool
    {
        return $this->verified;
    }

    public function getVerified(): bool
    {
        return $this->verified;
    }

    public function setVerified(bool $verified): static
    {
        $this->verified = $verified;

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
            $shelf->addBook($this);
        }

        return $this;
    }

    public function removeShelf(Shelf $shelf): static
    {
        if ($this->shelves->removeElement($shelf)) {
            $shelf->removeBook($this);
        }

        return $this;
    }

    public function getAgeCategory(): ?int
    {
        return $this->ageCategory;
    }

    public function setAgeCategory(?int $ageCategory): static
    {
        $this->ageCategory = $ageCategory;

        return $this;
    }

    /**
     * @throws \Exception
     */
    public function getUuid(): string
    {
        if ($this->uuid === null) {
            $this->uuid = $this->generateUuid();
        }

        return $this->uuid;
    }

    public function getPageNumber(): ?int
    {
        return $this->pageNumber;
    }

    public function setPageNumber(?int $pageNumber): void
    {
        $this->pageNumber = $pageNumber;
    }

    /**
     * @return Collection<int, KoboSyncedBook>
     */
    public function getKoboSyncedBook(): Collection
    {
        return $this->koboSyncedBooks;
    }

    public function addKoboSyncedBook(KoboSyncedBook $koboSyncedBook): static
    {
        if (!$this->koboSyncedBooks->contains($koboSyncedBook)) {
            $this->koboSyncedBooks->add($koboSyncedBook);
            $koboSyncedBook->setBook($this);
        }

        return $this;
    }

    public function removeKoboSyncedBook(KoboSyncedBook $koboSyncedBook): static
    {
        if ($this->koboSyncedBooks->removeElement($koboSyncedBook)) {
            // set the owning side to null (unless already changed)
            if ($koboSyncedBook->getBook() === $this) {
                $koboSyncedBook->setBook(null);
            }
        }

        return $this;
    }
}
