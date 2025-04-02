<?php

namespace App\Controller;

use App\Entity\Conge;
use App\Entity\CongeType;
use App\Repository\CongeRepository;
use App\Repository\EmployeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\CongeStatus;
use App\Entity\CongeReason;

#[Route('/conge')]
class CongeController extends AbstractController
{
    #[Route('/', name: 'app_conge_index', methods: ['GET'])]
    public function index(CongeRepository $congeRepository, EmployeRepository $employeRepository): Response
    {
        $conges = $congeRepository->findAll();
        
        // Get employee names for each leave request
        $congesWithEmployeeNames = [];
        foreach ($conges as $conge) {
            $employe = $employeRepository->find($conge->getIdEmploye());
            $employeName = $employe ? $employe->getNom() . ' ' . $employe->getPrenom() : 'Inconnu';
            
            $congesWithEmployeeNames[] = [
                'conge' => $conge,
                'employeName' => $employeName
            ];
        }
        
        return $this->render('conge/index.html.twig', [
            'conges' => $congesWithEmployeeNames,
        ]);
    }

    #[Route('/new', name: 'app_conge_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $conge = new Conge();
        $form = $this->createForm(CongeType::class, $conge);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Calculate number of days between start and end dates
            $interval = $conge->getLeaveStart()->diff($conge->getLeaveEnd());
            $conge->setNumberOfDays($interval->days + 1); // +1 because the end date is inclusive
            
            $entityManager->persist($conge);
            $entityManager->flush();

            $this->addFlash('success', 'La demande de congé a été créée avec succès.');
            return $this->redirectToRoute('app_conge_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('conge/new.html.twig', [
            'conge' => $conge,
            'form' => $form,
        ]);
    }

    #[Route('/{idConge}', name: 'app_conge_show', methods: ['GET'])]
    public function show(Conge $conge, EmployeRepository $employeRepository): Response
    {
        $employe = $employeRepository->find($conge->getIdEmploye());
        $employeName = $employe ? $employe->getNom() . ' ' . $employe->getPrenom() : 'Inconnu';
        
        return $this->render('conge/show.html.twig', [
            'conge' => $conge,
            'employeName' => $employeName,
        ]);
    }

    #[Route('/{idConge}/edit', name: 'app_conge_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Conge $conge, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(CongeType::class, $conge);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Recalculate number of days if dates changed
            $interval = $conge->getLeaveStart()->diff($conge->getLeaveEnd());
            $conge->setNumberOfDays($interval->days + 1);
            
            $entityManager->flush();

            $this->addFlash('success', 'La demande de congé a été mise à jour avec succès.');
            return $this->redirectToRoute('app_conge_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('conge/edit.html.twig', [
            'conge' => $conge,
            'form' => $form,
        ]);
    }

    #[Route('/{idConge}', name: 'app_conge_delete', methods: ['POST'])]
    public function delete(Request $request, Conge $conge, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$conge->getId(), $request->request->get('_token'))) {
            $entityManager->remove($conge);
            $entityManager->flush();
            $this->addFlash('success', 'La demande de congé a été supprimée avec succès.');
        }

        return $this->redirectToRoute('app_conge_index', [], Response::HTTP_SEE_OTHER);
    }
    
    #[Route('/{idConge}/approve', name: 'app_conge_approve', methods: ['GET', 'POST'])]
    public function approve(Conge $conge, EntityManagerInterface $entityManager): Response
    {
        $conge->setStatus(CongeStatus::ACCEPTE);
        $entityManager->flush();
        
        $this->addFlash('success', 'La demande de congé a été approuvée.');
        return $this->redirectToRoute('app_conge_index');
    }
    
    #[Route('/{idConge}/refuse', name: 'app_conge_refuse', methods: ['GET', 'POST'])]
    public function refuse(Conge $conge, EntityManagerInterface $entityManager): Response
    {
        $conge->setStatus(CongeStatus::REFUSE);
        $entityManager->flush();
        
        $this->addFlash('success', 'La demande de congé a été refusée.');
        return $this->redirectToRoute('app_conge_index');
    }
}