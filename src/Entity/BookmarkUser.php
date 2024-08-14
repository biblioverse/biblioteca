<?php

namespace App\Entity;

use App\Repository\BookmarkUserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: BookmarkUserRepository::class)]
class BookmarkUser
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: true)]
    private ?float $percent = null;

    #[ORM\Column(nullable: true)]
    private ?float $sourcePercent = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $locationValue = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $locationType = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $locationSource = null;

    #[ORM\ManyToOne(inversedBy: 'bookmarkUsers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user;

    #[ORM\ManyToOne(inversedBy: 'bookmarkUsers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Book $book;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTimeInterface $updated;

    public function __construct(?Book $book, ?User $user)
    {
        $this->book = $book;
        $this->user = $user;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPercent(): ?float
    {
        return $this->percent;
    }

    public function getPercentAsInt(): ?int
    {
        return $this->percent === null ? null : intval($this->percent * 100);
    }

    public function setPercent(?float $percent): static
    {
        $this->percent = $percent;

        return $this;
    }

    public function getSourcePercent(): ?float
    {
        return $this->sourcePercent;
    }

    public function getSourcePercentAsInt(): ?int
    {
        return $this->sourcePercent === null ? null : intval($this->sourcePercent * 100);
    }

    public function setSourcePercent(?float $sourcePercent): static
    {
        $this->sourcePercent = $sourcePercent;

        return $this;
    }

    public function getLocationValue(): ?string
    {
        return $this->locationValue;
    }

    public function setLocationValue(?string $locationValue): static
    {
        $this->locationValue = $locationValue;

        return $this;
    }

    public function getLocationType(): ?string
    {
        return $this->locationType;
    }

    public function setLocationType(?string $locationType): static
    {
        $this->locationType = $locationType;

        return $this;
    }

    public function getLocationSource(): ?string
    {
        return $this->locationSource;
    }

    public function setLocationSource(?string $locationSource): static
    {
        $this->locationSource = $locationSource;

        return $this;
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

    public function hasLocation(): bool
    {
        return $this->locationValue !== null;
    }

    public function setBook(?Book $book): self
    {
        $this->book = $book;

        return $this;
    }

    public function getBook(): ?Book
    {
        return $this->book;
    }

    public function getUpdated(): ?\DateTimeInterface
    {
        return $this->updated;
    }

    public function setUpdated(?\DateTimeInterface $updated): void
    {
        $this->updated = $updated;
    }
}
