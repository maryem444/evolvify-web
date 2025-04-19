<?php

namespace App\Controller;
use App\Entity\StatusEntretien;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\EntretienRepository;

class EntretiensController extends AbstractController
{
    #[Route('/Entretiens', name: 'app_Entretiens')]
    public function index(EntretienRepository $repo): Response
    {
        $entretien = $repo->getOffreAndCandidatDetails();  
        return $this->render('Recrutement/Entretiens.html.twig', [
            'entretien' => $entretien 
        ]);
    }

}
