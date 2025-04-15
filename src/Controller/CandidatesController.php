<?php

namespace App\Controller;
use App\Entity\User;
use App\Entity\Role;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\CandidatesRepository;


class CandidatesController extends AbstractController
{
    #[Route('/Candidates', name: 'app_Candidates')]

    public function listCandidates(EntityManagerInterface $entityManager): Response
    {  
        // Récupérer toutes les offres depuis la base de données
        $Candidates = $entityManager->getRepository(User::class)->findAll();
       // Filter out the candidates who have the 'CONDIDAT' role
       $filteredCandidates = array_filter($Candidates, function($candidate) {
        // Comparer avec la valeur de l'énum Role::CONDIDAT->value
        return $candidate->getRole()->value === Role::CONDIDAT->value;
    });

       // Render only the candidates with 'CONDIDAT' role
        return $this->render('Recrutement/Candidates.html.twig', [
            'Candidates' => $filteredCandidates,
          ]);

    }
}