<?php

namespace App\Controller\Kobo;

use Devdot\Monolog\Parser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;

#[Route('/user/kobo')]
class KoboDeviceLogsController extends AbstractController
{
    public function __construct(
        #[Autowire(param: 'kernel.logs_dir')]
        protected string $kernelLogsDir,
        #[Autowire(param: 'kernel.environment')]
        protected string $kernelEnvironment,
    ) {
    }

    #[Route('/logs', name: 'app_kobodevice_user_logs', methods: ['GET'])]
    public function logs(): Response
    {
        if (!$this->getUser() instanceof UserInterface) {
            throw $this->createAccessDeniedException();
        }

        $records = [];

        try {
            $parser = new Parser($this->kernelLogsDir.'/kobo.'.$this->kernelEnvironment.'-'.date('Y-m-d').'.log');

            $records = $parser->get();
        } catch (\Exception $e) {
            $this->addFlash('warning', $e->getMessage());
        }

        return $this->render('kobodevice_user/logs.html.twig', [
            'records' => $records,
        ]);
    }
}
