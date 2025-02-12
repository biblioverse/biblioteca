<?php

namespace App\Controller;

use App\Config\ConfigValue;
use App\Entity\InstanceConfiguration;
use App\Form\InstanceConfigurationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/configuration')]
final class InstanceConfigurationController extends AbstractController
{
    #[Route('', name: 'app_instance_configuration_index', methods: ['GET'])]
    public function index(ParameterBagInterface $parameterBagInterface, ConfigValue $configValue): Response
    {
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException();
        }

        $documentedParams = [
            'ALLOW_BOOK_RELOCATION' => 'Allow book relocation',
            'BOOK_FOLDER_NAMING_FORMAT' => '{authorFirst}/{author}/{serie}/{title}',
            'BOOK_FILE_NAMING_FORMAT' => '{serie}-{serieIndex}-{title',
            'KOBO_PROXY_ENABLED' => 'Is the Kobo proxy enabled?',
            'KOBO_PROXY_USE_EVERYWHERE' => 'Use the Kobo proxy everywhere.',
            'KOBO_API_URL' => 'Url of the Kobo API. See the kobo instructions for more information',
            'KOBO_IMAGE_API_URL' => 'Url of the Kobo Image API. See the kobo instructions for more information',
            'KEPUBIFY_BIN' => 'Path to kepubify binary',
            'KOBO_READINGSERVICES_URL' => 'Url of the Kobo Reading Services. See the kbo instructions for more information',
            'TYPESENSE_KEY' => 'Typesense API key',
            'TYPESENSE_URL' => 'Typesense URL',
        ];
        foreach ($documentedParams as $key => $value) {
            $documentedParams[$key] = [
                'value' => $parameterBagInterface->get($key),
                'description' => $value,
            ];
        }

        $availableParamsForEdit = ['GENERIC_SYSTEM_PROMPT'];
        $editableParams = [];
        foreach ($availableParamsForEdit as $key) {
            $editableParams[$key] = $configValue->resolve($key, true);
        }

        return $this->render('instance_configuration/index.html.twig', [
            'documentedParams' => $documentedParams,
            'editableParams' => $editableParams,
        ]);
    }

    #[Route('/{name}/edit', name: 'app_instance_configuration_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, string $name, EntityManagerInterface $entityManager): Response
    {
        $instanceConfiguration = $entityManager->getRepository(InstanceConfiguration::class)->findOneBy(['name' => $name]);
        if (!$instanceConfiguration instanceof InstanceConfiguration) {
            throw $this->createNotFoundException();
        }
        $form = $this->createForm(InstanceConfigurationType::class, $instanceConfiguration);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_instance_configuration_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('instance_configuration/edit.html.twig', [
            'instance_configuration' => $instanceConfiguration,
            'form' => $form,
        ]);
    }
}
