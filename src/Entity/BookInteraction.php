<?php

namespace App\Entity;

use App\Enum\ReadStatus;
use App\Enum\ReadingList;
use App\Repository\BookInteractionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: BookInteractionRepository::class)]
class BookInteraction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'bookInteractions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'bookInteractions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Book $book = null;

    /**
     * @deprecated Use ReadStatus enum instead
     */
    #[ORM\Column]
    private bool $finished = false;

    #[ORM\Column(nullable: true)]
    private ?int $readPages = null;


    /**
     * @deprecated Use RedingList enum instead
     */
    #[ORM\Column(nullable: false)]
    private bool $favorite = false;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $finishedDate = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['default' => '2024-01-12 00:00:00'])]
    #[Gedmo\Timestampable(on: 'create', )]
    private \DateTimeImmutable $created;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['default' => '2024-01-12 00:00:00'])]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTimeImmutable $updated = null;

    /**
     * @deprecated Use ReadStatus enum instead
     */
    #[ORM\Column]
    private bool $hidden = false;

    #[ORM\Column(enumType: ReadStatus::class, options: ['default' => ReadStatus::NotStarted])]
    private ReadStatus $readStatus = ReadStatus::NotStarted;

    #[ORM\Column(enumType: ReadingList::class, options: ['default' => ReadingList::NotDefined])]
    private ReadingList $readingList = ReadingList::NotDefined;

    #[ORM\Column(nullable: true)]
    private ?int $rating = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getBook(): ?Book
    {
        return $this->book;
    }

    public function setBook(?Book $book): static
    {
        $this->book = $book;

        return $this;
    }

    /**
     * @deprecated Use ReadStatus enum instead
     */
    public function isFinished(): bool
    {
        return $this->finished;
    }

    /**
     * @deprecated Use ReadStatus enum instead
     */
    public function setFinished(bool $finished): static
    {
        $this->finished = $finished;

        return $this;
    }

    /**
     * @deprecated Use readingList enum instead
     */
    public function isFavorite(): bool
    {
        return $this->favorite;
    }

    /**
     * @deprecated Use readingList enum instead
     */
    public function setFavorite(bool $favorite): static
    {
        $this->favorite = $favorite;

        return $this;
    }

    public function getFinishedDate(): ?\DateTimeInterface
    {
        return $this->finishedDate;
    }

    public function setFinishedDate(?\DateTimeInterface $finishedDate): static
    {
        $this->finishedDate = $finishedDate;

        return $this;
    }

    public function getCreated(): \DateTimeImmutable
    {
        return $this->created;
    }

    public function getUpdated(): ?\DateTimeImmutable
    {
        return $this->updated;
    }

    public function getReadPages(): ?int
    {
        if ($this->readStatus !== ReadStatus::Started) {
            return null;
        }

        return $this->readPages;
    }

    public function setReadPages(?int $readPages): void
    {
        $this->readPages = $readPages;
    }

    public function setUpdated(?\DateTimeImmutable $updated): void
    {
        $this->updated = $updated;
    }

    /**
     * @deprecated Use readinglist enum instead
     */
    public function isHidden(): bool
    {
        return $this->hidden;
    }

    /**
     * @deprecated Use readinglist enum instead
     */
    public function setHidden(bool $hidden): static
    {
        $this->hidden = $hidden;

        return $this;
    }

    public function getReadStatus(): ReadStatus
    {
        return $this->readStatus;
    }

    public function setReadStatus(ReadStatus $readStatus): static
    {
        $this->readStatus = $readStatus;

        return $this;
    }

    public function getReadingList(): ReadingList
    {
        return $this->readingList;
    }

    public function setReadingList(ReadingList $readingList): static
    {
        $this->readingList = $readingList;

        return $this;
    }

    public function getRating(): ?int
    {
        return $this->rating;
    }

    public function setRating(?int $rating): static
    {
        $this->rating = $rating;

        return $this;
    }
}
