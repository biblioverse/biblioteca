<?php

namespace App\Service;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;

class ThemeSelector
{
    public function __construct(
        private Security $security,
    ) {
    }

    public function isDark(): bool
    {
        return $this->getTheme() === 'dark';
    }

    public function getTheme(): ?string
    {
        $user = $this->security->getUser();

        if ($user instanceof User) {
            return $user->getTheme();
        }

        return null;
    }
}
