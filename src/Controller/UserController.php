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
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\JsonResponse;


class UserController extends AbstractController
{
    /**
     * Affiche la liste des utilisateurs et le formulaire d'ajout dans le modal.
     */
    #[Route('/users', name: 'user_index')]
    public function index(UserRepository $userRepository, Request $request, EntityManagerInterface $em): Response
    {
        // Récupérer tous les utilisateurs depuis la base de données
        $users = $userRepository->findAll();

        // Créer un formulaire vide pour l'ajout d'un nouvel utilisateur
        $user = new User();
        $form = $this->createForm(UserType::class, $user, [
            'is_new_user' => true // Option personnalisée pour le formulaire
        ]);

        // Rendre la vue avec la liste des utilisateurs et le formulaire
        return $this->render('user/index.html.twig', [
            'users' => $users,
            'photoBaseUrl' => $this->getParameter('profile_photo_base_url'), // URL des photos de profil
            'form' => $form->createView() // Transmettre le formulaire à Twig
        ]);
    }

    /**
     * Modifier un utilisateur existant
     */
    #[Route('/admin/user/edit/{id}', name: 'app_admin_user_edit')]
    public function editUser(Request $request, UserRepository $userRepository, int $id): Response
    {
        $user = $userRepository->find($id);

        if (!$user) {
            throw $this->createNotFoundException('Utilisateur non trouvé.');
        }

        return $this->render('admin/user/_edit_form.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * Supprimer un utilisateur
     */
    #[Route('/user/delete/{id}', name: 'user_delete')]
    public function delete(User $user, EntityManagerInterface $em): Response
    {
        // Supprimer l'utilisateur
        $em->remove($user);
        $em->flush();

        // Redirection après suppression
        return $this->redirectToRoute('user_index');
    }

    /**
     * Ajouter un nouvel utilisateur
     */
    #[Route("/admin/user/add", name: "app_admin_user_add", methods: ["POST"])]
    public function addUser(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Vérifier le type de contenu et récupérer les données appropriées
        $contentType = $request->headers->get('Content-Type');

        if (strpos($contentType, 'application/json') !== false) {
            // Pour les requêtes JSON (comme Postman)
            $data = json_decode($request->getContent(), true);
            $firstname = $data['firstname'] ?? null;
            $lastname = $data['lastname'] ?? null;
            $email = $data['email'] ?? null;
            $role = $data['role'] ?? null;
        } else {
            // Pour les requêtes de formulaire standard
            $firstname = $request->request->get('firstname');
            $lastname = $request->request->get('lastname');
            $email = $request->request->get('email');
            $role = $request->request->get('role');
        }

        // Validation basique des données requises
        if (empty($firstname) || empty($lastname) || empty($email) || empty($role)) {
            $this->addFlash('error', 'Tous les champs sont obligatoires');
            return $this->redirectToRoute('user_index');
        }

        // Vérifier si l'email existe déjà
        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            $this->addFlash('error', 'Un utilisateur avec cet email existe déjà');
            return $this->redirectToRoute('user_index');
        }

        try {
            // Créer un nouvel utilisateur
            $user = new User();
            $user->setFirstname($firstname);
            $user->setLastname($lastname);
            $user->setEmail($email);
            $user->setRole($role);

            // Date d'entrée par défaut à aujourd'hui
            $user->setJoiningDate(new \DateTime());

            // Persister l'entité
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Utilisateur ajouté avec succès');

            // Pour les requêtes API, retourner une réponse JSON
            if (strpos($contentType, 'application/json') !== false) {
                return $this->json([
                    'success' => true,
                    'message' => 'Utilisateur ajouté avec succès',
                    'userId' => $user->getId()
                ], 201);
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Une erreur est survenue lors de l\'ajout de l\'utilisateur: ' . $e->getMessage());

            // Pour les requêtes API, retourner une erreur JSON
            if (strpos($contentType, 'application/json') !== false) {
                return $this->json([
                    'success' => false,
                    'message' => 'Une erreur est survenue: ' . $e->getMessage()
                ], 400);
            }
        }
        // Check if this is an AJAX request
        if ($request->isXmlHttpRequest()) {
            // Return JSON response for AJAX requests
            return $this->json([
                'success' => true,
                'message' => 'Employé ajouté avec succès'
            ]);
        }

        // For non-AJAX requests, use flash messages and redirect
        $this->addFlash('success', 'Employé ajouté avec succès');
        return $this->redirectToRoute('user_index');
    }
}
