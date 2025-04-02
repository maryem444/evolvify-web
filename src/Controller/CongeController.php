<?php

namespace App\Controller;

use App\Repository\CongeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CongeController extends AbstractController
{
    #[Route('/conges', name: 'conges_list')]
    public function listConges(CongeRepository $congeRepository): Response
    {
        $conges = $congeRepository->getCongeListQB(); // Utilisation de la méthode personnalisée

        return $this->render('conges/list.html.twig', [
            'conges' => $conges,
        ]);
    }
}