<?php

namespace App\Entity;

use App\Repository\KoboSyncedBookRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

#[ORM\Entity(repositoryClass: KoboSyncedBookRepository::class)]
#[ORM\UniqueConstraint(name: 'kobo_synced_book_unique', columns: ['book_id', 'kobo_device_id'])]
class KoboSyncedBook
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'koboSyncedBooks')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Book $book = null;

    #[ORM\ManyToOne(inversedBy: 'koboSyncedBooks')]
    #[ORM\JoinColumn(nullable: true)]
    private ?KoboDevice $koboDevice = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    #[Gedmo\Timestampable(on: 'create')]
    private \DateTimeImmutable $created;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    #[Gedmo\Timestampable(on: 'update')]
    private ?\DateTimeImmutable $updated = null;

    public function __construct()
    {
        $this->created = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getKoboDevice(): ?KoboDevice
    {
        return $this->koboDevice;
    }

    public function setKoboDevice(?KoboDevice $koboDevice): static
    {
        $this->koboDevice = $koboDevice;

        return $this;
    }

    public function getCreated(): ?\DateTimeImmutable
    {
        return $this->created;
    }

    public function setCreated(\DateTimeImmutable $created): static
    {
        $this->created = $created;

        return $this;
    }

    public function getUpdated(): ?\DateTimeImmutable
    {
        return $this->updated;
    }

    public function setUpdated(?\DateTimeImmutable $updated): static
    {
        $this->updated = $updated;

        return $this;
    }
}
