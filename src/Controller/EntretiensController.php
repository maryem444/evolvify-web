<?php

// src/Controller/EntretiensController.php
namespace App\Controller;

use App\Entity\Entretien;
use App\Entity\StatusEntretien;
use App\Repository\EntretienRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class EntretiensController extends AbstractController
{
    #[Route('/Entretiens', name: 'app_Entretiens')]
    public function index(EntretienRepository $repo): Response
    {
        $entretien = $repo->getOffreAndCandidatDetails();
        return $this->render('Recrutement/Entretiens.html.twig', [
            'entretien' => $entretien,
        ]);
    }

    #[Route('/accepter-entretien/{id}', name: 'accepter_entretien')]
    public function accepterEntretien(
        int $id,
        MailerInterface $mailer,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        UserRepository $userRepo
    ): Response {
        $entretien = $entityManager->getRepository(Entretien::class)->find($id);

        if (!$entretien) {
            throw $this->createNotFoundException('Entretien non trouvé');
        }

        $candidat = $userRepo->find($entretien->getIdCondidate());
        if (!$candidat) {
            throw $this->createNotFoundException('Candidat non trouvé');
        }

        $emailCandidat = $candidat->getEmail();
        if (!$emailCandidat) {
            throw $this->createNotFoundException('Email du candidat manquant');
        }

        try {
            $email = (new Email())
                ->from('cherif.sarra@esprit.tn')
                ->to($emailCandidat)
                ->subject('Votre entretien')
                ->html('
                 <p>Nous avons le plaisir de vous annoncer que vous avez été <strong>accepté(e)</strong> pour le poste suite à votre entretien chez <strong>Evolvify</strong>.</p>
                <p>Nous vous contacterons très prochainement pour finaliser les détails et organiser votre intégration.</p>
                <p>Encore bravo et bienvenue dans l\'équipe !</p>
                <br>
                <p>Cordialement,<br>L\'équipe RH d\'Evolvify</p>
                ');
   

            $mailer->send($email);
            $this->addFlash('success', 'Email envoyé avec succès au candidat.');


            $candidat->setRole('EMPLOYEE');
            $entityManager->persist($candidat);

            $entretien->setStatus(StatusEntretien::ACCEPTE);
            $entityManager->persist($entretien);

            $entityManager->flush();
        } catch (\Exception $e) {
            $logger->error('Erreur : ' . $e->getMessage());
            throw $this->createNotFoundException('Une erreur est survenue : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_Entretiens');
    }

    #[Route('/refuser-entretien/{id}', name: 'refuser_entretien')]
    public function refuserEntretien(
        int $id,
        MailerInterface $mailer,
        EntityManagerInterface $entityManager,
        LoggerInterface $logger,
        UserRepository $userRepo
    ): Response {
        $entretien = $entityManager->getRepository(Entretien::class)->find($id);

        if (!$entretien) {
            throw $this->createNotFoundException('Entretien non trouvé');
        }

        $candidat = $userRepo->find($entretien->getIdCondidate());
        if (!$candidat) {
            throw $this->createNotFoundException('Candidat non trouvé');
        }

        $emailCandidat = $candidat->getEmail();
        if (!$emailCandidat) {
            throw $this->createNotFoundException('Email du candidat manquant');
        }

        try {
            $email = (new Email())
                ->from('cherif.sarra@esprit.tn')
                ->to($emailCandidat)
                ->subject('Votre entretien')
                ->html('
                <p>Nous avons le regret de vous informer que, suite à votre entretien chez <strong>Evolvify</strong>, nous avons décidé de ne pas donner suite à votre candidature pour le poste.</p>
                <p>Nous tenons à vous remercier d\'avoir pris le temps de participer à ce processus de sélection et de nous avoir fait part de votre parcours professionnel.</p>
                <p>Nous vous souhaitons une pleine réussite dans vos recherches futures et espérons que vous trouverez une opportunité qui correspondra à vos compétences et à vos aspirations.</p>
                <br>
                <p>Cordialement,<br>L\'équipe RH d\'Evolvify</p>
            ');
            
   

            $mailer->send($email);
            $this->addFlash('success', 'Email envoyé avec succès au candidat.');


            
           

            $entretien->setStatus(StatusEntretien::REFUSE);
            $entityManager->persist($entretien);

            $entityManager->flush();
        } catch (\Exception $e) {
            $logger->error('Erreur : ' . $e->getMessage());
            throw $this->createNotFoundException('Une erreur est survenue : ' . $e->getMessage());
        }

        return $this->redirectToRoute('app_Entretiens');
    }
}
