<?php

namespace App\Entity;

use App\Repository\KoboDeviceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: KoboDeviceRepository::class)]
#[ORM\UniqueConstraint(name: 'kobo_access_key', columns: ['access_key'])]
#[UniqueEntity(fields: ['accessKey'])]
class KoboDevice
{
    use RandomGeneratorTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank(allowNull: false)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name;

    #[ORM\ManyToOne(inversedBy: 'kobos')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(exactly: 32)]
    #[Assert\NotBlank(allowNull: false)]
    #[Assert\Regex(pattern: '/^[a-f0-9]+$/', message: 'Need to be Hexadecimal')]
    private ?string $accessKey = null;

    /**
     * @var Collection<int, Shelf>
     */
    #[ORM\ManyToMany(targetEntity: Shelf::class, inversedBy: 'koboDevices', cascade: ['persist'])]
    #[ORM\JoinTable(name: 'shelf_kobo')]
    private Collection $shelves;

    /**
     * @var Collection<int, KoboSyncedBook>
     */
    #[ORM\OneToMany(mappedBy: 'koboDevice', targetEntity: KoboSyncedBook::class, orphanRemoval: true)]
    private Collection $koboSyncedBooks;

    /**
     * @var bool
     */
    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $forceSync = false;

    public function __construct()
    {
        $this->shelves = new ArrayCollection();
        $this->accessKey = $this->generateRandomString(32);
        $this->koboSyncedBooks = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return Collection<int, Shelf>
     */
    public function getShelves(): Collection
    {
        return $this->shelves;
    }

    public function setName(?string $name): KoboDevice
    {
        $this->name = $name;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getAccessKey(): ?string
    {
        return $this->accessKey;
    }

    public function setAccessKey(?string $accessKey): void
    {
        $this->accessKey = $accessKey;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    /**
     * @param Collection<int, Shelf> $collection
     * @return void
     */
    public function setShelves(Collection $collection): void
    {
        $this->shelves = $collection;
    }

    public function addShelf(Shelf $shelf): void
    {
        if ($this->shelves->contains($shelf)) {
            return;
        }
        $this->shelves->add($shelf);
    }

    /**
     * @return Collection<int, KoboSyncedBook>
     */
    public function getKoboSyncedBooks(): Collection
    {
        return $this->koboSyncedBooks;
    }

    public function addKoboSyncedBook(KoboSyncedBook $koboSyncedBook): static
    {
        if (!$this->koboSyncedBooks->contains($koboSyncedBook)) {
            $this->koboSyncedBooks->add($koboSyncedBook);
            $koboSyncedBook->setKoboDevice($this);
        }

        return $this;
    }

    public function removeKoboSyncedBook(KoboSyncedBook $koboSyncedBook): static
    {
        if ($this->koboSyncedBooks->removeElement($koboSyncedBook)) {
            // set the owning side to null (unless already changed)
            if ($koboSyncedBook->getKoboDevice() === $this) {
                $koboSyncedBook->setKoboDevice(null);
            }
        }

        return $this;
    }

    public function isForceSync(): bool
    {
        return $this->forceSync;
    }

    public function setForceSync(bool $forceSync): self
    {
        $this->forceSync = $forceSync;

        return $this;
    }

    public function removeShelf(Shelf $shelf): void
    {
        $this->shelves->removeElement($shelf);
    }
}
