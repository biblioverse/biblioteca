<?php

namespace App\Entity;

use App\Enum\AgeCategory;
use App\Enum\ReadingList;
use App\Enum\ReadStatus;
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

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private \DateTimeImmutable $created;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTimeImmutable $updated = null;

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

    #[ORM\Column(type: Types::DATE_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $publishDate = null;

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
    #[ORM\OrderBy(['updated' => 'ASC'])]
    private Collection $bookInteractions;
    /**
     * @var Collection<int, BookmarkUser>
     */
    #[ORM\OneToMany(mappedBy: 'book', targetEntity: BookmarkUser::class, orphanRemoval: true)]
    private Collection $bookmarkUsers;
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

    #[ORM\Column(enumType: AgeCategory::class, nullable: true)]
    private ?AgeCategory $ageCategory = null;

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
        $this->bookmarkUsers = new ArrayCollection();
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

    public function getCreated(): \DateTimeImmutable
    {
        return $this->created;
    }

    public function getLastInteraction(User $user): ?BookInteraction
    {
        foreach ($this->bookInteractions as $interaction) {
            if ($interaction->getUser() !== $user) {
                continue;
            }

            return $interaction;
        }

        return null;
    }

    public function setCreated(\DateTimeImmutable $created): void
    {
        $this->created = $created;
    }

    public function getUpdated(): ?\DateTimeImmutable
    {
        return $this->updated;
    }

    public function setUpdated(?\DateTimeImmutable $updated): void
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

    public function getPublishDate(): ?\DateTimeImmutable
    {
        return $this->publishDate;
    }

    public function setPublishDate(?\DateTimeImmutable $publishDate): static
    {
        $this->publishDate = $publishDate;

        return $this;
    }

    /**
     * @return array<string>
     */
    public function getAuthors(): array
    {
        return array_map(static fn ($item) => ucwords(strtolower($item), self::UCWORDS_SEPARATORS), $this->authors);
    }

    /**
     * @param array<string> $authors
     *
     * @return $this
     */
    public function setAuthors(array $authors): static
    {
        $this->authors = [];
        foreach ($authors as $author) {
            $this->addAuthor($author);
        }

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
        $this->bookInteractions->removeElement($bookInteraction);

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
        $this->tags = [];
        if (null === $tags) {
            return $this;
        }
        foreach ($tags as $tag) {
            $this->addTag($tag);
        }

        return $this;
    }

    public function getTagsString(): ?string
    {
        return implode(',', $this->tags ?? []);
    }

    public function setTagsString(?string $tags): static
    {
        $this->tags = explode(',', (string) $tags);

        return $this;
    }

    public function getAuthorsString(): ?string
    {
        return implode(',', $this->authors ?? ['unknown']);
    }

    public function setAuthorsString(?string $authors): static
    {
        $this->authors = explode(',', (string) $authors);

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

    public function getAgeCategory(): ?AgeCategory
    {
        return $this->ageCategory;
    }

    public function setAgeCategory(?AgeCategory $ageCategory): static
    {
        $this->ageCategory = $ageCategory;

        return $this;
    }

    public function getAgeCategoryLabel(): ?string
    {
        return $this->ageCategory?->label() ?? 'enum.agecategories.notset';
    }

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
        // set the owning side to null (unless already changed)
        if ($this->koboSyncedBooks->removeElement($koboSyncedBook) && $koboSyncedBook->getBook() === $this) {
            $koboSyncedBook->setBook(null);
        }

        return $this;
    }

    public function setUuid(?string $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    /**
     * @return Collection<int, BookmarkUser>
     */
    public function getBookmarkUsers(): Collection
    {
        return $this->bookmarkUsers;
    }

    /**
     * @param Collection<int, BookmarkUser> $bookmarkUsers
     */
    public function setBookmarkUsers(Collection $bookmarkUsers): self
    {
        $this->bookmarkUsers = $bookmarkUsers;

        return $this;
    }

    public function getUsers(): object
    {
        $return = [
            'read' => [],
            'favorite' => [],
            'hidden' => [],
        ];

        foreach ($this->getBookInteractions() as $interaction) {
            $user = $interaction->getUser();

            $userId = $user->getId();
            if ($interaction->getReadStatus() === ReadStatus::Finished) {
                $return['read'][] = $userId;
            }
            if ($interaction->getReadingList() === ReadingList::ToRead) {
                $return['favorite'][] = $userId;
            }
            if ($interaction->getReadingList() === ReadingList::Ignored) {
                $return['hidden'][] = $userId;
            }
        }

        return (object) $return;
    }

    public function __clone(): void
    {
        $this->id = null;
        $this->uuid = $this->generateUuid();
    }

    public function isSummaryEmpty(): bool
    {
        return $this->summary === null || $this->summary === '';
    }

    public function isTagsEmpty(): bool
    {
        return $this->tags === null || $this->tags === [];
    }
}
