<?php

namespace App\Controller;

use App\Entity\Suggestion;
use App\Repository\SuggestionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SuggestionController extends AbstractController
{
    #[Route('/suggestion', name: 'app_suggestion')]
    public function index(SuggestionRepository $suggestionRepository): Response
    {
        $suggestions = $suggestionRepository->findAll();

        return $this->render('suggestion/index.html.twig', [
            'suggestions' => $suggestions,
        ]);
    }

    #[Route('/suggestion/{id}/accept', name: 'app_suggestion_accept')]
    public function accept(Suggestion $suggestion, EntityManagerInterface $entityManager): Response
    {
        $book = $suggestion->getBook();

        switch ($suggestion->getField()) {
            case 'summary':
                $book->setSummary($suggestion->getSuggestion());
                break;
            case 'tags':
                $value = json_decode($suggestion->getSuggestion(), true, 512, JSON_THROW_ON_ERROR);
                if (!is_array($value)) {
                    throw $this->createNotFoundException('Invalid tags format');
                }
                $value = array_map(function ($item) {
                    if (!is_string($item)) {
                        return '';
                    }

                    return trim($item);
                }, $value);
                $book->setTags($value);
                break;
            default:
                throw $this->createNotFoundException('Invalid field');
        }

        $entityManager->remove($suggestion);
        $entityManager->persist($book);
        $entityManager->flush();

        $this->addFlash('success', 'Suggestion accepted');

        return $this->redirectToRoute('app_suggestion');
    }

    #[Route('/suggestion/{id}/refuse', name: 'app_suggestion_delete')]
    public function delete(Suggestion $suggestion, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($suggestion);
        $entityManager->flush();

        $this->addFlash('success', 'Suggestion deleted');

        return $this->redirectToRoute('app_suggestion');
    }
}
