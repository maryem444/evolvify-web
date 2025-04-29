<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfilePhotoType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\SecurityBundle\Security;


class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile', methods: ['GET', 'POST'])]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Check if user is authenticated
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // Get the current user with proper type hinting
        /** @var User $user */
        $user = $this->getUser();

        // Handle form submission for personal information
        if ($request->isMethod('POST')) {
            // Get form data
            $firstname = $request->request->get('firstname');
            $lastname = $request->request->get('lastname');
            $numTel = $request->request->get('numTel');
            $birthdayDate = $request->request->get('birthdayDate');
            $gender = $request->request->get('gender');

            // Update user information
            $user->setFirstname($firstname);
            $user->setLastname($lastname);

            // Handle numTel - ensure it's an integer or null
            if ($numTel !== null && $numTel !== '') {
                $user->setNumTel((int)$numTel);
            } else {
                $user->setNumTel(null);
            }

            if ($gender !== null && in_array($gender, ['HOMME', 'FEMME'])) {
                $user->setGender($gender);
            }

            // Handle birthday date if provided and if it hasn't been edited before
            if ($birthdayDate && !$user->isBirthdateEdited()) {
                try {
                    $birthdayDateTime = new \DateTime($birthdayDate);
                    $user->setBirthdayDate($birthdayDateTime);
                    // Mark as edited so it can't be changed again
                    $user->setBirthdateEdited(true);
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Invalid date format for birthday');
                }
            }

            // Save changes to database
            try {
                $entityManager->flush();
                $this->addFlash('success', 'Profile updated successfully!');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error updating profile: ' . $e->getMessage());
            }

            // Redirect to prevent form resubmission
            return $this->redirectToRoute('app_profile');
        }

        // Create form for photo upload
        $photoForm = $this->createForm(ProfilePhotoType::class, $user);

        // Create response with user data
        $response = $this->render('profile/index.html.twig', [
            'user' => $user,
            'photoForm' => $photoForm->createView(),
            'isOwnProfile' => true, // <--- add this line!
        ]);

        // Set cache headers to prevent back-button issues after logout
        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0, private');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT');

        return $response;
    }

    #[Route('/send-message', name: 'app_send_message', methods: ['POST'])]
    public function sendMessage(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        // Check if user is authenticated
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        /** @var User $sender */
        $sender = $this->getUser();

        // Get form data
        $recipientId = $request->request->get('recipient_id');
        $subject = $request->request->get('subject');
        $messageContent = $request->request->get('message');

        // Find recipient
        $recipient = $entityManager->getRepository(User::class)->find($recipientId);

        if (!$recipient) {
            $this->addFlash('error', 'Recipient not found');
            return $this->redirectToRoute('app_profile');
        }

        // Create and send email
        try {
            $email = (new Email())
                ->from(new \Symfony\Component\Mime\Address($sender->getEmail(), 'Evolvify'))
                ->to($recipient->getEmail())
                ->subject('Evolvify - ' . $subject)
                ->html(
                    $this->renderView('emails/contact_message.html.twig', [
                        'sender' => $sender,
                        'recipient' => $recipient,
                        'subject' => $subject,
                        'message' => $messageContent,
                        'appName' => 'Evolvify'
                    ])
                )
                ->priority(Email::PRIORITY_NORMAL);

            // Utilisez la méthode headers() pour ajouter des headers si disponible
            if (method_exists($email, 'getHeaders')) {
                $email->getHeaders()->addTextHeader('X-Auto-Response-Suppress', 'OOF, DR, RN, NRN, AutoReply');
            }

            $mailer->send($email);
            $this->addFlash('success', 'Your message has been sent to ' . $recipient->getFirstname());
        } catch (\Exception $e) {
            $this->addFlash('error', 'Failed to send message: ' . $e->getMessage());
        }

        // Redirect back to profile page
        return $this->redirectToRoute('app_profile');
    }

    #[Route('/profile/{id}', name: 'app_view_profile', methods: ['GET'])]
    public function viewProfile(int $id, EntityManagerInterface $entityManager): Response
    {
        // Check if user is authenticated
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // Find the user profile to view
        $viewUser = $entityManager->getRepository(User::class)->find($id);

        if (!$viewUser) {
            $this->addFlash('error', 'User not found');
            return $this->redirectToRoute('app_dashboard');
        }

        // Create form for photo upload (needed for template)
        $photoForm = $this->createForm(ProfilePhotoType::class, $viewUser);

        return $this->render('profile/view.html.twig', [
            'user' => $viewUser,
            'photoForm' => $photoForm->createView(),
            'isOwnProfile' => $viewUser?->id_employe === $this->getUser()?->id_employe
        ]);
    }

    #[Route('/dashboard', name: 'app_dashboard')]
    public function dashboard(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if (
            !in_array('ROLE_CHEF_PROJET', $user->getRoles()) &&
            !in_array('ROLE_RESPONSABLE_RH', $user->getRoles())
        ) {
            throw new AccessDeniedException('You do not have permission to access this page.');
        }

        return $this->render('dashboard.html.twig');
    }

    #[Route('/users', name: 'app_users')]
    public function users(EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        if (!in_array('ROLE_RESPONSABLE_RH', $user->getRoles())) {
            throw new AccessDeniedException('You do not have permission to access this page.');
        }

        $users = $entityManager->getRepository(User::class)->findAll();

        return $this->render('user/index.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/profile/update-photo', name: 'app_profile_update_photo')]
    public function updatePhoto(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Create the form and handle the request
        $form = $this->createForm(ProfilePhotoType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Get the uploaded file
            $photoFile = $form->get('photo')->getData();

            if ($photoFile) {
                // Create a unique filename
                $filename = strtolower($user->getFirstname()) . '_' .
                    strtolower($user->getLastname()) . '_' .
                    time() . '.' . $photoFile->guessExtension();

                try {
                    // Use Symfony's public directory 
                    $uploadPath = $this->getParameter('kernel.project_dir') . '/public/uploads/';

                    // Create directory if it doesn't exist
                    if (!is_dir($uploadPath)) {
                        mkdir($uploadPath, 0777, true);
                    }

                    // Move the file
                    $photoFile->move($uploadPath, $filename);

                    // Store path relative to public directory
                    $user->setProfilePhoto('uploads/' . $filename);

                    $entityManager->flush();

                    $this->addFlash('success', 'Profile photo updated successfully!');
                    return $this->redirectToRoute('app_profile');
                } catch (\Exception $e) {
                    $this->addFlash('error', 'Error uploading photo: ' . $e->getMessage());
                }
            }
        }

        // Render the form template, passing the form as 'photoForm'
        return $this->render('profile/photo_form.html.twig', [
            'photoForm' => $form->createView(),
            'user' => $user,
        ]);
    }

    #[Route('/user/{id}/profile', name: 'app_user_profile')]
    public function showProfile(User $user, Security $security): Response
    {
        $currentUser = $security->getUser();
    
        $isOwnProfile = false;
        if ($currentUser instanceof User) {
            $isOwnProfile = ($currentUser->getId() === $user->getId());
        }
    
        $photoForm = $this->createForm(ProfilePhotoType::class, $user);
    
        return $this->render('profile/index.html.twig', [
            'photoForm' => $photoForm->createView(),
            'user' => $user,
            'isOwnProfile' => $isOwnProfile,
        ]);
    }
    
    
    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }
}
