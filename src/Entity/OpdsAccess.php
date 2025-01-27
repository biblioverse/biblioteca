<?php

namespace App\Entity;

use App\Repository\OpdsAccessRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OpdsAccessRepository::class)]
class OpdsAccess
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $token = null;

    public function __construct(#[ORM\ManyToOne(inversedBy: 'opdsAccesses')]
        #[ORM\JoinColumn(nullable: true)]
        private ?User $user)
    {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getToken(): ?string
    {
        return $this->token;
    }

    public function setToken(?string $token): static
    {
        $this->token = $token;

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
}
