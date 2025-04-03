<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class UserController extends AbstractController
{
    #[Route('/users', name: 'user_index')]
    public function index(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        return $this->render('user/index.html.twig', [
            'users' => $users,
            'photoBaseUrl' => $this->getParameter('profile_photo_base_url')
        ]);
    }
    #[Route('/users/add-form', name: 'add_user_form')]
    public function addUserForm(): Response
    {
        return $this->render('user/addUser.html.twig');
    }

    #[Route('/user/new', name: 'user_new')]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($user);
            $em->flush();

            return $this->redirectToRoute('user_index');
        }

        return $this->render('user/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/user/edit/{id}', name: 'user_edit')]
    public function edit(User $user, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            return $this->redirectToRoute('user_index');
        }

        return $this->render('user/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/user/delete/{id}', name: 'user_delete')]
    public function delete(User $user, EntityManagerInterface $em): Response
    {
        $em->remove($user);
        $em->flush();

        return $this->redirectToRoute('user_index');
    }

    #[Route('/user/create-ajax', name: 'user_create_ajax', methods: ['POST'])]
    public function createAjax(Request $request, EntityManagerInterface $em): Response
    {
        // Get data from the request
        $data = json_decode($request->getContent(), true);

        // Create new user
        $user = new User();
        $user->setFirstname($data['firstname'] ?? '');
        $user->setLastname($data['lastname'] ?? '');
        $user->setEmail($data['email'] ?? '');
        $user->setRole($data['role'] ?? '');

        // Save user to database
        $em->persist($user);
        $em->flush();

        return $this->json([
            'success' => true,
            'message' => 'User created successfully',
            'user' => [
                'id' => $user->getId(),
                'firstname' => $user->getFirstname(),
                'lastname' => $user->getLastname(),
                'email' => $user->getEmail(),
                'role' => $user->getRole()
            ]
        ]);
    }
}
