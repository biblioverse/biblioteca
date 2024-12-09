<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileType;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/user')]
class UserController extends AbstractController
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }

    #[Route('/', name: 'app_user_index', methods: ['GET'])]
    public function index(UserRepository $userRepository): Response
    {
        return $this->render('user/index.html.twig', [
            'users' => $userRepository->findAll(),
            'age_categories' => array_flip(User::AGE_CATEGORIES),
        ]);
    }

    #[Route('/profile', name: 'app_user_profile')]
    public function profile(Request $request, EntityManagerInterface $entityManager, Security $security, RequestStack $requestStack): Response
    {
        $user = $security->getUser();
        if (!$user instanceof User) {
            throw new \RuntimeException('User must be an instance of User');
        }
        $form = $this->createForm(ProfileType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword !== null && trim($plainPassword.'') !== '') {
                if (!is_string($plainPassword)) {
                    throw new \RuntimeException('Password must be a string');
                }
                $user->setPassword($this->passwordHasher->hashPassword(
                    $user,
                    $plainPassword
                ));
            }

            $entityManager->persist($user);
            $entityManager->flush();

            $requestStack->getSession()->set('_locale', $user->getLanguage());

            $this->addFlash('success', 'Profile updated successfully');

            return $this->redirectToRoute('app_user_profile', [], Response::HTTP_SEE_OTHER);
        }

        $opds = $user->getOpdsAccesses()->first();

        if ($opds === false) {
            $opds = null;
        }

        return $this->render('user/profile.html.twig', [
            'form' => $form->createView(),
            'opds_access' => $opds,
            'user' => $user,
            'tab' => $request->get('tab', 'profile'),
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->add('plainPassword', PasswordType::class, [
            'mapped' => false,
            'required' => true,
            'help' => 'Required for new users',
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();
            if (!is_string($plainPassword)) {
                throw new \RuntimeException('Password must be a string');
            }
            $user->setPassword($this->passwordHasher->hashPassword(
                $user,
                $plainPassword
            ));
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/new.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_user_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $plainPassword = $form->get('plainPassword')->getData();

            if ($plainPassword !== null) {
                if (!is_string($plainPassword)) {
                    throw new \RuntimeException('Password must be a string');
                }
                $user->setPassword($this->passwordHasher->hashPassword(
                    $user,
                    $plainPassword
                ));
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_user_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('user/edit.html.twig', [
            'user' => $user,
            'form' => $form,
        ]);
    }
}
