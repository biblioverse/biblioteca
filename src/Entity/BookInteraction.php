<?php

namespace App\Entity;

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

    #[ORM\Column]
    private bool $finished = false;

    #[ORM\Column(nullable: true)]
    private ?int $readPages = null;

    #[ORM\Column(nullable: false)]
    private bool $favorite = false;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $finishedDate = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['default' => '2024-01-12 00:00:00'])]
    #[Gedmo\Timestampable(on: 'create', )]
    private \DateTimeImmutable $created;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['default' => '2024-01-12 00:00:00'])]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTimeImmutable $updated;

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

    public function isFinished(): bool
    {
        return $this->finished;
    }

    public function setFinished(bool $finished): static
    {
        $this->finished = $finished;

        return $this;
    }

    public function isFavorite(): bool
    {
        return $this->favorite;
    }

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
}
