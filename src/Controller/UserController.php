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
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Address;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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

    #[Route('/admin/user/{id}', name: 'app_admin_user_show', methods: ['GET'])]
public function show(User $user): JsonResponse
{
    // Strip "ROLE_" prefix from the role
    $roleWithPrefix = $user->getRoles()[0] ?? 'ROLE_EMPLOYEE';
    $role = str_replace('ROLE_', '', $roleWithPrefix);
    
    return $this->json([
        'id' => $user->getId(),
        'firstname' => $user->getFirstname(),
        'lastname' => $user->getLastname(),
        'email' => $user->getEmail(),
        'role' => $role // Now sending without "ROLE_" prefix
    ]);
}

    #[Route('/user/delete/{id}', name: 'user_delete', methods: ['DELETE'])]
    public function deleteUser(int $id, EntityManagerInterface $em): JsonResponse
    {
        // Recherche de l'utilisateur par ID
        $user = $em->getRepository(User::class)->find($id);

        // Vérifie si l'utilisateur a été trouvé
        if (!$user) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Utilisateur non trouvé'
            ], 404);  // 404 Not Found
        }

        try {
            // Suppression de l'utilisateur
            $em->remove($user);
            $em->flush();

            return new JsonResponse([
                'status' => 'success',
                'message' => 'Utilisateur supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Une erreur est survenue : ' . $e->getMessage()
            ], 500);  // 500 Internal Server Error
        }
    }

    /**
     * Ajouter un nouvel utilisateur et envoyer un email de création de mot de passe
     */
    #[Route("/admin/user/add", name: "app_admin_user_add", methods: ["POST"])]
    public function addUser(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        try {
            // Vérifier le type de contenu et récupérer les données appropriées
            $contentType = $request->headers->get('Content-Type');
            $isAjax = $request->isXmlHttpRequest();

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
                if ($isAjax) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Tous les champs sont obligatoires'
                    ], 400);
                }

                $this->addFlash('error', 'Tous les champs sont obligatoires');
                return $this->redirectToRoute('user_index');
            }

            // Vérifier si l'email existe déjà
            $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
            if ($existingUser) {
                if ($isAjax) {
                    return $this->json([
                        'success' => false,
                        'message' => 'Un utilisateur avec cet email existe déjà'
                    ], 400);
                }

                $this->addFlash('error', 'Un utilisateur avec cet email existe déjà');
                return $this->redirectToRoute('user_index');
            }

            // Créer un nouvel utilisateur
            $user = new User();
            $user->setFirstname($firstname);
            $user->setLastname($lastname);
            $user->setEmail($email);
            $user->setRole($role);

            // Date d'entrée par défaut à aujourd'hui
            $user->setJoiningDate(new \DateTime());

            // Générer un token unique pour la réinitialisation du mot de passe
            $token = bin2hex(random_bytes(32)); // Génère un token aléatoire sécurisé
            $user->setResetToken($token);
            $user->setResetTokenExpiration(new \DateTime('+24 hours')); // Expire dans 24 heures

            // Persister l'entité
            $entityManager->persist($user);
            $entityManager->flush();

            // Envoyer l'email de création de mot de passe
            $resetUrl = $this->generateUrl('app_reset_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

            // Email optimisé pour éviter le dossier spam
            $email = (new TemplatedEmail())
                ->from(new Address('maryemsassi.dev@gmail.com', 'Evolvify'))
                ->to($user->getEmail())
                ->subject('Bienvenue - Créez votre mot de passe')
                ->htmlTemplate('emails/welcome.html.twig');

            // Add text template only if it exists
            if (file_exists($this->getParameter('kernel.project_dir') . '/templates/emails/welcome.txt.twig')) {
                $email->textTemplate('emails/welcome.txt.twig');
            }

            $email->context([
                'user' => $user,
                'resetUrl' => $resetUrl,
            ]);

            // Add anti-spam headers
            $headers = $email->getHeaders();
            $headers->addIdHeader('Message-ID', uniqid() . '@evolvify.com');
            $headers->addTextHeader('X-Auto-Response-Suppress', 'OOF, DR, RN, NRN, AutoReply');
            $headers->addTextHeader('List-Unsubscribe', '<mailto:maryemsassi.dev@gmail.com?subject=unsubscribe>');

            $mailer->send($email);

            // Réponse en fonction du type de requête
            if ($isAjax) {
                return $this->json([
                    'success' => true,
                    'message' => 'Employé ajouté avec succès. Un email a été envoyé pour la création du mot de passe.',
                    'userId' => $user->getId()
                ], 201);
            }

            $this->addFlash('success', 'Employé ajouté avec succès. Un email a été envoyé pour la création du mot de passe.');
            return $this->redirectToRoute('user_index');
        } catch (\Exception $e) {
            // Journalisation de l'erreur pour le débogage
            error_log('Erreur lors de l\'ajout d\'utilisateur: ' . $e->getMessage());

            // Réponse en fonction du type de requête
            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => false,
                    'message' => 'Une erreur est survenue: ' . $e->getMessage()
                ], 500);
            }

            $this->addFlash('error', 'Une erreur est survenue lors de l\'ajout de l\'utilisateur');
            return $this->redirectToRoute('user_index');
        }
    }
    /**
 * Mettre à jour un utilisateur
 */
#[Route("/admin/user/update/{id}", name: "app_admin_user_update", methods: ["POST"])]
public function updateUser(int $id, Request $request, EntityManagerInterface $entityManager): Response
{
    try {
        // Log the beginning of the update process
        error_log('Début de la mise à jour pour utilisateur ID: ' . $id);
        
        // Récupérer l'utilisateur à partir de l'ID
        $user = $entityManager->getRepository(User::class)->find($id);

        if (!$user) {
            error_log('Utilisateur non trouvé avec ID: ' . $id);
            if ($request->isXmlHttpRequest()) {
                return $this->json([
                    'success' => false,
                    'message' => 'Utilisateur non trouvé'
                ], 404);
            }

            $this->addFlash('error', 'Utilisateur non trouvé');
            return $this->redirectToRoute('user_index');
        }
        
        // Log current user data before update
        error_log('Données actuelles de l\'utilisateur: ' . json_encode([
            'id' => $user->getId(),
            'firstname' => $user->getFirstname(),
            'lastname' => $user->getLastname(),
            'email' => $user->getEmail(),
            'role' => $user->getRole() // or $user->getRoles()[0] depending on your implementation
        ]));

        // Récupérer les données de la requête
        $isAjax = $request->isXmlHttpRequest();
        $contentType = $request->headers->get('Content-Type');
        error_log('Type de requête: ' . ($isAjax ? 'AJAX' : 'Standard') . ', Content-Type: ' . $contentType);

        if (strpos($contentType, 'application/json') !== false) {
            // Pour les requêtes JSON
            $data = json_decode($request->getContent(), true);
            $firstname = $data['firstname'] ?? null;
            $lastname = $data['lastname'] ?? null;
            $email = $data['email'] ?? null;
            $role = $data['role'] ?? null;
            error_log('Données JSON reçues: ' . json_encode($data));
        } else {
            // Pour les requêtes de formulaire standard
            $firstname = $request->request->get('firstname');
            $lastname = $request->request->get('lastname');
            $email = $request->request->get('email');
            $role = $request->request->get('role');
            error_log('Données de formulaire reçues: ' . json_encode([
                'firstname' => $firstname,
                'lastname' => $lastname,
                'email' => $email,
                'role' => $role
            ]));
        }

        // Validation basique
        if (empty($firstname) || empty($lastname) || empty($email) || empty($role)) {
            error_log('Validation échouée - champs manquants: ' . 
                      (empty($firstname) ? 'firstname ' : '') .
                      (empty($lastname) ? 'lastname ' : '') .
                      (empty($email) ? 'email ' : '') .
                      (empty($role) ? 'role' : ''));
            
            if ($isAjax) {
                return $this->json([
                    'success' => false,
                    'message' => 'Tous les champs sont obligatoires'
                ], 400);
            }

            $this->addFlash('error', 'Tous les champs sont obligatoires');
            return $this->redirectToRoute('user_index');
        }

        // Vérifier si l'email existe déjà pour un autre utilisateur
        $existingUser = $entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser && $existingUser->getId() !== $id) {
            error_log('Email déjà utilisé par un autre utilisateur ID: ' . $existingUser->getId());
            if ($isAjax) {
                return $this->json([
                    'success' => false,
                    'message' => 'Un utilisateur avec cet email existe déjà'
                ], 400);
            }

            $this->addFlash('error', 'Un utilisateur avec cet email existe déjà');
            return $this->redirectToRoute('user_index');
        }

        // Mettre à jour l'utilisateur
        $user->setFirstname($firstname);
        $user->setLastname($lastname);
        $user->setEmail($email);
        $user->setRole($role);
        error_log('Mise à jour avec les valeurs: ' . json_encode([
            'firstname' => $firstname,
            'lastname' => $lastname,
            'email' => $email,
            'role' => $role
        ]));

        // Persister les modifications
        $entityManager->flush();
        error_log('Utilisateur mis à jour avec succès ID: ' . $id);

        // Réponse en fonction du type de requête
        if ($isAjax) {
            return $this->json([
                'success' => true,
                'message' => 'Utilisateur mis à jour avec succès'
            ], 200);
        }

        $this->addFlash('success', 'Utilisateur mis à jour avec succès');
        return $this->redirectToRoute('user_index');
    } catch (\Exception $e) {
        // Journalisation détaillée de l'erreur
        error_log('Erreur lors de la mise à jour de l\'utilisateur ID ' . $id . ': ' . $e->getMessage());
        error_log('Trace: ' . $e->getTraceAsString());

        // Réponse en fonction du type de requête
        if ($request->isXmlHttpRequest()) {
            return $this->json([
                'success' => false,
                'message' => 'Une erreur est survenue: ' . $e->getMessage()
            ], 500);
        }

        $this->addFlash('error', 'Une erreur est survenue lors de la mise à jour de l\'utilisateur');
        return $this->redirectToRoute('user_index');
    }
}
}
