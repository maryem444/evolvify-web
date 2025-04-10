<?php

namespace App\Controller;
use App\Entity\Offre;
use App\Form\OffreType;
use App\Entity\Status;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\OffreRepository;

class OffreController extends AbstractController
{
    #[Route('/Offre', name: 'app_Offre')]
    public function listOffres(EntityManagerInterface $entityManager): Response
    {
        // Récupérer toutes les offres depuis la base de données
        $offres = $entityManager->getRepository(Offre::class)->findAll();

        return $this->render('Recrutement/Offre.html.twig', [
            'offres' => $offres,
        ]);
    }

    #[Route('/base/addoffre', name: 'add_Offre')]

    public function addOffre(Request $Request, ManagerRegistry $doctrine): Response
    {
        
        $Offre = new Offre();
        $form = $this->createForm(OffreType::class,$Offre);
        $form->handleRequest($Request);
        if ($form->isSubmitted() && $form->isValid()) {
            $manager = $doctrine->getManager();
            $offre = $form->getData();
            $manager->persist($Offre);
            $manager->flush();
            // 🔥 Redirection vers base.html.twig après soumission
            return $this->redirectToRoute('app_Offre');
        }
        return $this->render('Recrutement/addOffre.html.twig',[
            
            'form' => $form->createView(),
            
        ]);


    }
    #[Route('/deleteOffre/{idOffre}', name: 'delete_Offre')]
    public function deleteOffre(ManagerRegistry $mr, OffreRepository $repo, $idOffre): Response
    {
    $manager = $mr->getManager();
    $offre = $repo->find($idOffre);

    if (!$offre) {
        throw $this->createNotFoundException("L'offre avec l'ID $idOffre n'existe pas.");
    }

    $manager->remove($offre);
    $manager->flush();

    return $this->redirectToRoute("app_Offre");
    }

    #[Route('/updateOffre/{idOffre}', name: 'offre_update')]
    public function updateOffre(ManagerRegistry $mr, OffreRepository $repo, $idOffre, Request $request): Response
    {
    $manager = $mr->getManager();

    // Trouver l'offre par son ID
    $offre = $repo->find($idOffre);

    if (!$offre) {
        throw $this->createNotFoundException("L'offre n'a pas été trouvée.");
    }

    // Créer le formulaire pour modifier l'offre
    $form = $this->createForm(OffreType::class, $offre);
    $form->handleRequest($request);

    // Si le formulaire est soumis et valide, on persiste les changements
    if ($form->isSubmitted() && $form->isValid()) {
        $manager->persist($offre);
        $manager->flush();

        // Rediriger vers la page des offres après la mise à jour
        return $this->redirectToRoute("app_Offre");
    }

    // Afficher le formulaire de mise à jour
    return $this->render('Recrutement/addOffre.html.twig', [
        'form' => $form->createView(),
    ]);

    }

    #[Route('/recherche', name: 'recherche_offres', methods: ['GET'])]
    public function search(Request $request, OffreRepository $offreRepository): Response
{
    $keyword = strtolower(trim($request->query->get('keyword', '')));

    if (empty($keyword)) {
        $offres = $offreRepository->findAll();
    } else {
        $offres = $offreRepository->search($keyword); // Utilise la méthode search du repository
    }

    return $this->render('Recrutement/Offre.html.twig', [
        'offres' => $offres,
        'keyword' => $keyword,
    ]);
}


}