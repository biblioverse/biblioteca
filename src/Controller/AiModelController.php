<?php

namespace App\Controller;

use App\Ai\Communicator\CommunicatorDefiner;
use App\Ai\Prompt\PromptFactory;
use App\Ai\Prompt\SummaryPrompt;
use App\Ai\Prompt\TagPrompt;
use App\Config\ConfigValue;
use App\Entity\AiModel;
use App\Entity\Book;
use App\Entity\User;
use App\Form\AiConfigurationType;
use App\Form\AiModelType;
use App\Repository\AiModelRepository;
use App\Repository\BookRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/ai/model')]
final class AiModelController extends AbstractController
{
    public function __construct(private readonly AiModelRepository $aiModelRepository, private readonly ConfigValue $configValue, private readonly PromptFactory $promptFactory, private readonly CommunicatorDefiner $communicatorDefiner, private readonly BookRepository $bookRepository)
    {
    }

    #[Route(name: 'app_ai_model_index')]
    public function index(Request $request): Response
    {
        $allModels = $this->aiModelRepository->findAllIndexed();

        $values = [
            'AI_SUMMARIZATION_MODEL' => $allModels[(int) $this->configValue->resolve('AI_SUMMARIZATION_MODEL', true)] ?? null,
            'AI_TAG_MODEL' => $allModels[(int) $this->configValue->resolve('AI_TAG_MODEL', true)] ?? null,
            'AI_SEARCH_MODEL' => $allModels[(int) $this->configValue->resolve('AI_SEARCH_MODEL', true)] ?? null,
            'AI_ASSISTANT_MODEL' => $allModels[(int) $this->configValue->resolve('AI_ASSISTANT_MODEL', true)] ?? null,
            'AI_CONTEXT_MODEL' => $allModels[(int) $this->configValue->resolve('AI_CONTEXT_MODEL', true)] ?? null,
            'AI_SUMMARY_PROMPT' => $this->configValue->resolve('AI_SUMMARY_PROMPT', true),
            'AI_TAG_PROMPT' => $this->configValue->resolve('AI_TAG_PROMPT', true),
        ];

        $form = $this->createForm(AiConfigurationType::class, $values);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            if (!is_array($data)) {
                return $this->redirectToRoute('app_ai_model_index');
            }
            foreach ($data as $key => $value) {
                if ($value instanceof AiModel) {
                    $value = $value->getId();
                }
                $this->configValue->update((string) $key, $value);
            }
            $this->addFlash('success', 'Configuration updated successfully');
        }

        return $this->render('ai_model/index.html.twig', [
            'ai_models' => $allModels,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/test/{id}', name: 'app_ai_model_test', methods: ['GET', 'POST'])]
    public function test(Request $request, AiModel $aiModel): Response
    {
        $book = $this->bookRepository->findOneBy([]);
        if (!$book instanceof Book) {
            $this->addFlash('error', 'No books');

            return $this->redirectToRoute('app_ai_model_index');
        }

        try {
            $communicator = $this->communicatorDefiner->getSpecificCommunicator($aiModel);

            $user = $this->getUser();
            if (!$user instanceof User) {
                $this->addFlash('error', 'You must be logged in to use this feature');

                return $this->redirectToRoute('app_ai_model_index');
            }
            $tagPrompt = $this->promptFactory->getPrompt(TagPrompt::class, $book);
            $summaryPrompt = $this->promptFactory->getPrompt(SummaryPrompt::class, $book);

            $tagPromptResponse = '';
            $summaryPromptResponse = '';
            if ($request->isMethod('POST')) {
                $tagPromptResponse = $communicator->interrogate($tagPrompt->getPrompt());
                $tagPromptResponse = $tagPrompt->convertResult($tagPromptResponse);
                $summaryPromptResponse = $communicator->interrogate($summaryPrompt->getPrompt());
                $summaryPromptResponse = $summaryPrompt->convertResult($summaryPromptResponse);
            }
        } catch (\Throwable $e) {
            $this->addFlash('error', 'Test failed: '.$e->getMessage());

            return $this->redirectToRoute('app_ai_model_edit', ['id' => $aiModel->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('ai_model/show.html.twig', [
            'ai_model' => $aiModel,
            'book' => $book,
            'tagPrompt' => $tagPrompt,
            'tagPromptResponse' => $tagPromptResponse,
            'summaryPrompt' => $summaryPrompt,
            'summaryPromptResponse' => $summaryPromptResponse,
        ]);
    }

    #[Route('/new', name: 'app_ai_model_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $aiModel = new AiModel();
        $aiModel->setSystemPrompt($this->configValue->resolve('GENERIC_SYSTEM_PROMPT'));
        $form = $this->createForm(AiModelType::class, $aiModel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($aiModel);
            $entityManager->flush();

            return $this->redirectToRoute('app_ai_model_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('ai_model/new.html.twig', [
            'ai_model' => $aiModel,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_ai_model_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, AiModel $aiModel, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AiModelType::class, $aiModel);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_ai_model_test', ['id' => $aiModel->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('ai_model/edit.html.twig', [
            'ai_model' => $aiModel,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_ai_model_delete')]
    public function delete(AiModel $aiModel, EntityManagerInterface $entityManager): Response
    {
        $entityManager->remove($aiModel);
        $entityManager->flush();

        return $this->redirectToRoute('app_ai_model_index', [], Response::HTTP_SEE_OTHER);
    }
}
