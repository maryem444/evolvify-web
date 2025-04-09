<?php

namespace App\Repository;

use App\Entity\Trajet;
use App\Entity\Abonnement;
use App\Entity\MoyenTransport;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\Query\Expr\Join;

/**
 * @extends ServiceEntityRepository<Trajet>
 */
class TrajetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Trajet::class);
    }

    /**
     * Récupère la liste de tous les trajets
     * @return Trajet[] 
     */
    public function findAllTrajets(): array
    {
        return $this->createQueryBuilder('t')
            ->orderBy('t.id_T', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie si un employé est actif dans la table Abonnement
     * @param int $id_employe
     * @return bool
     */
    public function isEmployeActif(int $id_employe): bool
    {
        $abonnement = $this->getEntityManager()
            ->getRepository(Abonnement::class)
            ->findOneBy(['id_employe' => $id_employe, 'status' => 'ACTIF']);

        return $abonnement !== null;
    }

    /**
     * Récupère les moyens de transport disponibles
     * @return MoyenTransport[] 
     */
    public function findAvailableMoyensTransport(): array
    {
        return $this->getEntityManager()
            ->getRepository(MoyenTransport::class)
            ->findBy(['status' => 'DISPONIBLE']);
    }

    /**
     * Récupère un trajet par son ID avec vérification de l'employé et du moyen de transport
     * @param int $id
     * @param int $id_employe
     * @param int $id_moyen
     * @return Trajet|null
     */
    public function findTrajetByIdAndValidation(int $id, int $id_employe, int $id_moyen): ?Trajet
    {
        // Vérifier si l'employé est actif
        if (!$this->isEmployeActif($id_employe)) {
            return null; // L'employé n'est pas actif
        }

        // Vérifier si le moyen de transport est disponible
        $moyenTransport = $this->getEntityManager()
            ->getRepository(MoyenTransport::class)
            ->findOneBy(['id_moyen' => $id_moyen, 'status' => 'DISPONIBLE']);

        if (!$moyenTransport) {
            return null; // Le moyen de transport n'est pas disponible
        }

        // Récupérer le trajet avec les conditions de validation
        return $this->createQueryBuilder('t')
            ->where('t.id_T = :id')
            ->andWhere('t.id_employe = :id_employe')
            ->andWhere('t.moyen_transport = :id_moyen') // Change 'id_moyen' to 'moyen_transport' to align with the entity relationship
            ->setParameter('id', $id)
            ->setParameter('id_employe', $id_employe)
            ->setParameter('id_moyen', $id_moyen)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
