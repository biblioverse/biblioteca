<?php

namespace App\Controller;

use App\Ai\Communicator\CommunicatorDefiner;
use App\Ai\Context\ContextBuilder;
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
    #[Route(name: 'app_ai_model_index')]
    public function index(Request $request, AiModelRepository $aiModelRepository, ConfigValue $configValue): Response
    {
        $allModels = $aiModelRepository->findAllIndexed();

        $values = [
            'AI_SUMMARIZATION_MODEL' => $allModels[(int) $configValue->resolve('AI_SUMMARIZATION_MODEL', true)] ?? null,
            'AI_TAG_MODEL' => $allModels[(int) $configValue->resolve('AI_TAG_MODEL', true)] ?? null,
            'AI_SEARCH_MODEL' => $allModels[(int) $configValue->resolve('AI_SEARCH_MODEL', true)] ?? null,
            'AI_SUMMARY_PROMPT' => $configValue->resolve('AI_SUMMARY_PROMPT', true),
            'AI_TAG_PROMPT' => $configValue->resolve('AI_TAG_PROMPT', true),
        ];

        $form = $this->createForm(AiConfigurationType::class, $values);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            if (!is_array($data)) {
                return $this->redirectToRoute('app_ai_model_index');
            }
            foreach ($data as $key => $value) {
                $configValue->update((string) $key, (string) $value);
            }
            $this->addFlash('success', 'Configuration updated successfully');
        }

        return $this->render('ai_model/index.html.twig', [
            'ai_models' => $allModels,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/test/{id}', name: 'app_ai_model_test', methods: ['GET', 'POST'])]
    public function test(Request $request, AiModel $aiModel, PromptFactory $promptFactory, CommunicatorDefiner $communicatorDefiner, ContextBuilder $contextBuilder, BookRepository $bookRepository): Response
    {
        $book = $bookRepository->findOneBy([]);
        if (!$book instanceof Book) {
            $this->addFlash('error', 'No books');

            return $this->redirectToRoute('app_ai_model_index');
        }

        try {
            $communicator = $communicatorDefiner->getSpecificCommunicator($aiModel);

            $user = $this->getUser();
            if (!$user instanceof User) {
                $this->addFlash('error', 'You must be logged in to use this feature');

                return $this->redirectToRoute('app_ai_model_index');
            }
            $tagPrompt = $promptFactory->getPrompt(TagPrompt::class, $book, $user);
            $summaryPrompt = $promptFactory->getPrompt(SummaryPrompt::class, $book, $user);

            $initialTagPrompt = clone $tagPrompt;
            $initialSummaryPrompt = clone $summaryPrompt;

            $tagPrompt = $contextBuilder->getContext($aiModel, $tagPrompt);
            $summaryPrompt = $contextBuilder->getContext($aiModel, $summaryPrompt);
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
            'initialTagPrompt' => $initialTagPrompt,
            'tagPrompt' => $tagPrompt,
            'tagPromptResponse' => $tagPromptResponse,
            'initialSummaryPrompt' => $initialSummaryPrompt,
            'summaryPrompt' => $summaryPrompt,
            'summaryPromptResponse' => $summaryPromptResponse,
        ]);
    }

    #[Route('/new', name: 'app_ai_model_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ConfigValue $configValue): Response
    {
        $aiModel = new AiModel();
        $aiModel->setSystemPrompt($configValue->resolve('GENERIC_SYSTEM_PROMPT'));
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
