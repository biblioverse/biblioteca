<?php

namespace App\Entity;

use App\Kobo\SyncToken;
use App\Repository\KoboDeviceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: KoboDeviceRepository::class)]
#[ORM\UniqueConstraint(name: 'kobo_access_key', columns: ['access_key'])]
#[ORM\Index(columns: ['device_id'], name: 'kobo_device_id')]
#[ORM\Index(columns: ['access_key'], name: 'kobo_access_key')]
#[UniqueEntity(fields: ['accessKey'])]
class KoboDevice
{
    use RandomGeneratorTrait;
    public const KOBO_DEVICE_ID_HEADER = 'X-Kobo-Deviceid';
    public const KOBO_DEVICE_MODEL_HEADER = 'X-Kobo-Devicemodel';
    public const KOBO_SYNC_TOKEN_HEADER = 'X-Kobo-Synctoken';
    public const KOBO_SYNC_SHOULD_CONTINUE_HEADER = 'X-Kobo-Sync';
    public const KOBO_SYNC_MODE = 'X-Kobo-Sync-Mode';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank(allowNull: false)]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'kobos')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[ORM\Column(length: 255, nullable: true)]
    #[Assert\Length(exactly: 32)]
    #[Assert\NotBlank(allowNull: false)]
    #[Assert\Regex(pattern: '/^[a-f0-9]+$/', message: 'Need to be Hexadecimal')]
    private ?string $accessKey = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $deviceId = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $model = null;
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

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $forceSync = false;

    #[ORM\Column(type: 'boolean', options: ['default' => true])]
    private bool $syncReadingList = true;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $upstreamSync = false;

    #[ORM\Column(type: 'json', nullable: true, options: ['default' => null])]
    private ?array $lastSyncToken = null;

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

    /**
     * You can also set "archived" on the syncedBook instead of removing it.
     */
    public function removeKoboSyncedBook(KoboSyncedBook $koboSyncedBook): static
    {
        // set the owning side to null (unless already changed)
        if ($this->koboSyncedBooks->removeElement($koboSyncedBook) && $koboSyncedBook->getKoboDevice() === $this) {
            $koboSyncedBook->setKoboDevice(null);
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

    public function getDeviceId(): ?string
    {
        return $this->deviceId;
    }

    public function setDeviceId(?string $deviceId): self
    {
        $this->deviceId = $deviceId;

        return $this;
    }

    public function getModel(): ?string
    {
        return $this->model;
    }

    public function setModel(?string $model): self
    {
        $this->model = $model;

        return $this;
    }

    public function isUpstreamSync(): bool
    {
        return $this->upstreamSync;
    }

    public function setUpstreamSync(bool $upstreamSync): void
    {
        $this->upstreamSync = $upstreamSync;
    }

    public function isSyncReadingList(): bool
    {
        return $this->syncReadingList;
    }

    public function setSyncReadingList(bool $syncReadingList): void
    {
        $this->syncReadingList = $syncReadingList;
    }

    public function setLastSyncToken(?SyncToken $lastSyncToken): KoboDevice
    {
        $this->lastSyncToken = $lastSyncToken?->toArray();

        return $this;
    }

    public function getLastSyncToken(): ?SyncToken
    {
        if ($this->lastSyncToken === null) {
            return null;
        }

        return SyncToken::fromArray($this->lastSyncToken);
    }
}
