<?php

namespace App\Controller;

use App\Entity\Projet;
use App\Entity\StatutProjet;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use App\Form\ProjetType;
use App\Repository\ProjetRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twilio\Rest\Client;

class CardsController extends AbstractController
{
  // Dans App\Controller\CardsController.php
  #[Route('/projets/cards', name: 'projets_cards')]
  public function listProjetsCards(ProjetRepository $projetRepository): Response
  {
    // Récupérer l'utilisateur connecté
    $user = $this->getUser();

    // Passer l'utilisateur au repository pour filtrer les projets
    $projets = $projetRepository->getProjetListQB($user);
    
    // Calculer le nombre total de projets
    $totalProjets = count($projets);

    // Vérifier automatiquement les deadlines et envoyer des notifications
    $this->checkAndNotifyProjectDeadlines($projetRepository);

    return $this->render('projets/cards.html.twig', [
      'projets' => $projets,
      'totalProjets' => $totalProjets
    ]);
  }

  #[Route('/projets/cards/add', name: 'projet_add_card')]
  public function addProjet(Request $request, ManagerRegistry $doctrine): Response
  {
    $projet = new Projet();
    $projet->setStatus(StatutProjet::IN_PROGRESS);
    $form = $this->createForm(ProjetType::class, $projet);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $manager = $doctrine->getManager();
      $manager->persist($projet);
      $manager->flush();

      $this->addFlash('success', 'Le projet a été ajouté avec succès');

      // Rediriger explicitement vers la vue en cartes
      return $this->redirectToRoute('projets_cards');
    }

    return $this->render('projets/add.html.twig', [
      'form' => $form->createView(),
      'redirect_route' => 'projets_cards' // Passer la route de redirection au template
    ]);
  }

  #[Route('/projets/cards/delete/{id}', name: 'projet_delete_card', methods: ['POST', 'DELETE'])]
  public function deleteProjet(Projet $projet, ManagerRegistry $doctrine): Response
  {
    $manager = $doctrine->getManager();
    $manager->remove($projet);
    $manager->flush();

    $this->addFlash('success', 'Le projet a été supprimé avec succès');

    // Rediriger explicitement vers la vue en cartes
    return $this->redirectToRoute('projets_cards');
  }

  #[Route('/projets/cards/{id}/edit', name: 'projet_edit_card')]
  public function edit(Request $request, Projet $projet, EntityManagerInterface $entityManager): Response
  {
    $oldFilePath = $projet->getUploadedFiles(); // Stockez le chemin de l'ancien fichier

    $form = $this->createForm(ProjetType::class, $projet);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      /** @var UploadedFile $file */
      $file = $form->get('uploaded_files')->getData();

      if ($file) {
        $uploadsDirectory = $this->getParameter('uploads_directory');
        $newFilename = uniqid() . '.' . $file->guessExtension();

        try {
          // Supprimer l'ancien fichier s'il existe
          if ($oldFilePath) {
            $fullOldPath = $this->getParameter('kernel.project_dir') . '/public/' . $oldFilePath;
            if (file_exists($fullOldPath)) {
              unlink($fullOldPath);
            }
          }

          $file->move($uploadsDirectory, $newFilename);
          $projet->setUploadedFiles('uploads/' . $newFilename); // Stocker le nouveau chemin
        } catch (FileException $e) {
          $this->addFlash('danger', 'Erreur lors du téléchargement du fichier.');
        }
      }

      // Sauvegarder les modifications en base de données
      $entityManager->flush();

      $this->addFlash('success', 'Projet modifié avec succès !');

      // Rediriger explicitement vers la vue en cartes
      return $this->redirectToRoute('projets_cards');
    }

    return $this->render('projets/edit.html.twig', [
      'form' => $form->createView(),
      'projet' => $projet,
      'redirect_route' => 'projets_cards' // Passer la route de redirection au template
    ]);
  }

  #[Route('/projets/cards/{id}/details', name: 'projet_details_card')]
  public function details(Projet $projet, ProjetRepository $projetRepository): Response
  {
    // Vérifier automatiquement les deadlines ici aussi
    $this->checkAndNotifyProjectDeadlines($projetRepository);
    
    return $this->render('projets/details.html.twig', [
      'projet' => $projet
    ]);
  }

  /**
   * Méthode privée pour vérifier les deadlines et envoyer des notifications
   * Cette méthode est appelée automatiquement lors de l'accès à l'interface des cartes
   */
  private function checkAndNotifyProjectDeadlines(ProjetRepository $projetRepository): void
  {
    // Récupérer les projets dont la deadline est demain
    $projects = $projetRepository->findProjectsWithDeadlineTomorrow();
    
    // Si des projets ont été trouvés, envoyer des notifications
    if (!empty($projects)) {
      foreach ($projects as $project) {
        // Récupérer tous les employés assignés à ce projet
        $assignedUsers = $project->getAssignedUsers();
        
        if (count($assignedUsers) > 0) {
          foreach ($assignedUsers as $user) {
            // Vérifier que l'utilisateur a un numéro de téléphone
            if ($user->getNumTel()) {
              // Créer un message personnalisé pour l'employé
              $message = "Rappel: Le projet '{$project->getName()}' sur lequel vous travaillez a sa deadline demain ({$project->getEndDate()->format('d/m/Y')}).";
              
              // Convertir le numéro de téléphone (integer) en chaîne de caractères
              $phoneNumber = '+' . $user->getNumTel();
              
              // Envoyer le SMS
              $this->sendSmsNotification($phoneNumber, $message);
            }
          }
        }
      }
    }
  }

  /**
   * Route exposée pour la vérification manuelle des deadlines
   * Cette route peut être utilisée pour des tests ou pour des tâches planifiées
   */
  #[Route('/projets/check-deadlines', name: 'check_project_deadlines')]
  public function checkProjectDeadlines(ProjetRepository $projetRepository): Response
  {
    // Récupérer les projets dont la deadline est demain
    $projects = $projetRepository->findProjectsWithDeadlineTomorrow();
    
    // Compte des notifications envoyées
    $notificationsSent = 0;
    $notificationsFailed = 0;
    
    // Si des projets ont été trouvés, envoyer des notifications
    if (!empty($projects)) {
      foreach ($projects as $project) {
        // Récupérer tous les employés assignés à ce projet
        $assignedUsers = $project->getAssignedUsers();
        
        if (count($assignedUsers) > 0) {
          foreach ($assignedUsers as $user) {
            // Vérifier que l'utilisateur a un numéro de téléphone
            if ($user->getNumTel()) {
              // Créer un message personnalisé pour l'employé
              $message = "Rappel: Le projet '{$project->getName()}' sur lequel vous travaillez a sa deadline demain ({$project->getEndDate()->format('d/m/Y')}).";
              
              // Convertir le numéro de téléphone (integer) en chaîne de caractères
              $phoneNumber = '+' . $user->getNumTel();
              
              // Envoyer le SMS
              $success = $this->sendSmsNotification($phoneNumber, $message);
              
              if ($success) {
                $notificationsSent++;
              } else {
                $notificationsFailed++;
              }
            }
          }
        }
      }
    }

    return new Response("Vérification des deadlines terminée. $notificationsSent notifications envoyées avec succès. $notificationsFailed notifications échouées.");
  }

  /**
   * Méthode pour envoyer un SMS via l'API Twilio
   */
  private function sendSmsNotification(string $phoneNumber, string $message): bool
  {
    try {
      // Récupérer les informations d'identification Twilio depuis les variables d'environnement
      $sid = $_ENV['CTWILIO_ACCOUNT_SID'];
      $token = $_ENV['CTWILIO_AUTH_TOKEN'];
      $twilioNumber = $_ENV['CTWILIO_PHONE_NUMBER'];
      
      // Pour les tests, vous pouvez utiliser le numéro du chef de projet
      $useTestNumber = true; // Définir sur true pour les tests si nécessaire
      $recipientNumber = $useTestNumber ? $_ENV['CHEFDEPROJET_PHONE_NUMBER'] : $phoneNumber;
      
      // Créer un client Twilio
      $twilio = new Client($sid, $token);
      
      // Envoyer le SMS
      $twilio->messages->create(
        $recipientNumber,
        [
          'from' => $twilioNumber,
          'body' => $message
        ]
      );
      
      return true;
    } catch (\Exception $e) {
      // Journaliser l'erreur
      error_log('Erreur Twilio: ' . $e->getMessage());
      return false;
    }
  }
}