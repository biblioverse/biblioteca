<?php

namespace App\Controller;

use App\Entity\Shelf;
use App\Repository\ShelfRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ShelfController extends AbstractController
{
    #[Route('/shelf/{slug}', name: 'app_shelf')]
    public function index(Shelf $shelf): Response
    {

        return $this->render('shelf/index.html.twig', [
            'controller_name' => 'ShelfController',
        ]);
    }
}
