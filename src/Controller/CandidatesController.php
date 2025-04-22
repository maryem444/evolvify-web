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
        
        $Candidates = $entityManager->getRepository(User::class)->findAll();
        $filteredCandidates = array_filter($Candidates, function($candidate) {
         return $candidate->getRole() === Role::CONDIDAT->value;
    });

       
        return $this->render('Recrutement/Candidates.html.twig', [
            'Candidates' => $filteredCandidates,
          ]);

    }

}