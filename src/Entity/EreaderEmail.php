<?php

namespace App\Entity;

use App\Repository\EreaderEmailRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: EreaderEmailRepository::class)]
#[ORM\UniqueConstraint(name: 'user_email_unique', columns: ['user_id', 'email'])]
#[UniqueEntity(fields: ['user', 'email'], message: 'This email address is already registered for this user.')]
class EreaderEmail
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank(allowNull: false)]
    #[ORM\Column(length: 255, nullable: false)]
    private string $name;

    #[ORM\ManyToOne(inversedBy: 'ereaderEmails')]
    #[ORM\JoinColumn(nullable: false)]
    private User $user;

    #[Assert\NotBlank(allowNull: false)]
    #[Assert\Email]
    #[ORM\Column(length: 255, nullable: false)]
    private string $email;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): void
    {
        $this->user = $user;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }
}
