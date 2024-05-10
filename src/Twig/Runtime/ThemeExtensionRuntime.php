<?php

namespace App\Twig\Runtime;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\RuntimeExtensionInterface;

class ThemeExtensionRuntime implements RuntimeExtensionInterface
{
    public function __construct(private Security $security)
    {
        // Inject dependencies if needed
    }

    public function themedTemplate(string $value): string|array
    {
        $user = $this->security->getUser();

        if ($user instanceof User) {
            return ['themes/'.$user->getTheme().'/'.$value, $value];
        }

        return $value;
    }
}
