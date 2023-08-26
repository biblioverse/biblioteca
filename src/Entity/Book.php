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
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $title;

    #[ORM\Column(length: 255, unique: true)]
    private string $checksum;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $summary = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $eventDate = null;

    #[ORM\Column(length: 128, unique: true)]
    #[Gedmo\Slug(fields: ['title', 'id'], style: 'lower')]
    private string $slug;

    #[ORM\Column(length: 128, unique: false)]
    #[Gedmo\Slug(fields: ['serie'], style: 'lower', unique: false)]
    private string $serieSlug;

    #[ORM\Column(length: 128, unique: false)]
    #[Gedmo\Slug(fields: ['mainAuthor'], style: 'lower', unique: false)]
    private string $authorSlug;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private \DateTimeInterface $created;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
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

    #[ORM\Column(length: 255)]
    private string $mainAuthor;

    #[ORM\Column(length: 2, nullable: true)]
    private ?string $language = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $publisher = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $publishDate = null;

    /**
     * @var array <string>
     */
    #[ORM\Column(type: Types::ARRAY)]
    private array $authors = [];

    #[ORM\Column(length: 5)]
    private string $extension;

    #[ORM\Column(length: 5, nullable: true)]
    private ?string $imageExtension=null;

    /**
     * @var Collection<int, BookInteraction>
     */
    #[ORM\OneToMany(mappedBy: 'book', targetEntity: BookInteraction::class, orphanRemoval: true)]
    private Collection $bookInteractions;

    public function __construct()
    {
        $this->bookInteractions = new ArrayCollection();
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
        $this->title = $title;

        return $this;
    }

    public function getSlug():string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): void
    {
        $this->slug = $slug;
    }

    public function getEventDate(): ?\DateTimeInterface
    {
        return $this->eventDate;
    }

    public function setEventDate(?\DateTimeInterface $eventDate): self
    {
        $this->eventDate = $eventDate;

        return $this;
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
        if($serie===''){
            $serie=null;
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

    public function getMainAuthor(): string
    {
        return $this->mainAuthor;
    }

    public function setMainAuthor(string $mainAuthor): static
    {
        $this->mainAuthor = $mainAuthor;

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
     * @return array <string>
     */
    public function getAuthors(): array
    {
        return $this->authors;
    }

    /**
     * @param array<string> $authors
     * @return $this
     */
    public function setAuthors(array $authors): static
    {
        $this->authors = $authors;

        return $this;
    }

    public function addAuthor(string $author): static
    {
        $this->authors[] = $author;

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

    public function getSerieSlug(): string
    {
        return $this->serieSlug;
    }

    public function setSerieSlug(string $serieSlug): void
    {
        $this->serieSlug = $serieSlug;
    }

    public function getAuthorSlug(): string
    {
        return $this->authorSlug;
    }

    public function setAuthorSlug(string $authorSlug): void
    {
        $this->authorSlug = $authorSlug;
    }
}
