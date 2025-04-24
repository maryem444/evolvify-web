<?php

// src/Repository/EntretienRepository.php
namespace App\Repository;

use App\Entity\Entretien;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EntretienRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Entretien::class);
    }

    public function getOffreAndCandidatDetails(): array
    {
        $conn = $this->getEntityManager()->getConnection();
        $query = "
            SELECT 
                lo.id_liste_offres, lo.id_condidat, lo.id_offre, lo.status, lo.date_postulation,
                u.firstname AS nom_candidat, u.lastname AS prenom_candidat,
                o.titre AS titre_offre
            FROM liste_offres lo
            JOIN users u ON lo.id_condidat = u.id_employe
            JOIN offre o ON lo.id_offre = o.id_offre
        ";

        $stmt = $conn->prepare($query);
        $result = $stmt->executeQuery();

        $entretienList = [];

        while ($row = $result->fetchAssociative()) {
            $entretien = new Entretien();
            $entretien->setIdListOffre($row['id_liste_offres']);
            $entretien->setIdCondidate($row['id_condidat']);
            $entretien->setIdOffre($row['id_offre']);
            $entretien->setStatus(\App\Entity\StatusEntretien::from($row['status']));
            $entretien->setDatePostulation(new \DateTime($row['date_postulation']));
            $entretien->setNomCandidat($row['nom_candidat']);
            $entretien->setPrenomCandidat($row['prenom_candidat']);
            $entretien->setTitreOffre($row['titre_offre']);

            $entretienList[] = $entretien;
        }

        return $entretienList;
    }
}




