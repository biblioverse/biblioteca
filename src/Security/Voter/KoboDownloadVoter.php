<?php

namespace App\Security\Voter;

use App\Entity\Book;
use App\Entity\Shelf;
use App\Repository\BookInteractionRepository;
use App\Security\Token\PostAuthenticationTokenWithKoboDevice;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class KoboDownloadVoter extends Voter
{
    public const string KOBO_DOWNLOAD = 'KOBO_DOWNLOAD';

    public function __construct(private readonly BookInteractionRepository $bookInteractionRepository)
    {
    }

    #[\Override]
    protected function supports(string $attribute, mixed $subject): bool
    {
        return $attribute === self::KOBO_DOWNLOAD
            && $subject instanceof Book;
    }

    #[\Override]
    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        if (!$token instanceof PostAuthenticationTokenWithKoboDevice) {
            return false;
        }

        if (!$subject instanceof Book) {
            return false;
        }

        // allow if book is in kobo shelves
        $koboShelves = $token->getKoboDevice()->getShelves()->map(fn (Shelf $shelf) => $shelf->getId())->toArray();
        $bookShelves = $subject->getShelves()->map(fn (Shelf $shelf) => $shelf->getId())->toArray();
        if (array_intersect($koboShelves, $bookShelves) !== []) {
            return true;
        }

        // allow if in reading list
        $favorites = $this->bookInteractionRepository->getFavourite();
        foreach ($favorites as $favorite) {
            if ($favorite->getBook() === $subject) {
                return true;
            }
        }

        // Allow if user is admin
        $roles = $token->getUser()?->getRoles() ?? [];

        return in_array('ROLE_ADMIN', $roles, true);
    }
}
