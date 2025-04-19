<?php

namespace App\Repository;

use App\Entity\Entretien;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\Connection;

class EntretienRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Entretien::class);
    }
  

    

    public function getOffreAndCandidatDetails()
    {
    $LO = [];
    $query = "
        SELECT 
            u.firstname AS nom_candidat, 
            u.lastname AS prenom_candidat, 
            o.titre AS titre_offre,
            lo.date_postulation AS date_postulation  
        FROM liste_offres lo
        JOIN users u ON lo.id_condidat = u.id_employe
        JOIN offre o ON lo.id_offre = o.id_offre
    ";

    try {
        $conn = $this->getEntityManager()->getConnection();
        $stmt = $conn->prepare($query);            // Prépare la requête
        $result = $stmt->executeQuery();           // Exécute la requête -> retourne un objet Result

        while ($row = $result->fetchAssociative()) { // On utilise fetchAssociative() sur l'objet Result
            $listOf = new Entretien();

            $listOf->setNomCandidat($row['nom_candidat']);
            $listOf->setPrenomCandidat($row['prenom_candidat']);
            $listOf->setTitreOffre($row['titre_offre']);
            $listOf->setDatePostulation(new \DateTime($row['date_postulation']));

            $LO[] = $listOf;
        }
    } catch (\Exception $e) {
        echo "Erreur : " . $e->getMessage();
    }

    return $LO;
    }


    
    
}




