<?php

namespace App\Controller;

use App\Kobo\Proxy\KoboStoreProxy;
use GuzzleHttp\Exception\GuzzleException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/kobo/{accessKey}', name: 'kobo')]
class KoboUserProfile extends AbstractController
{
    public function __construct(
        protected KoboStoreProxy $koboStoreProxy,
    ) {
    }

    /**
     * @throws GuzzleException
     */
    #[Route('/v1/user/profile', name: 'userprofile')]
    public function userprofile(Request $request): Response
    {
        return $this->koboStoreProxy->proxy($request);
    }
}
