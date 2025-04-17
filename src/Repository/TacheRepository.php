<?php

namespace App\Repository;

use App\Entity\Tache;
use App\Entity\Projet;
use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Tache>
 */
class TacheRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tache::class);
    }

    /**
     * Récupère la liste de toutes les tâches.
     * @return Tache[]
     */
    public function getTacheListQB(): array
    {
        return $this->createQueryBuilder('t')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche des tâches par statut.
     * @param string $status
     * @return Tache[]
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche des tâches par priorité.
     * @param string $priority
     * @return Tache[]
     */
    public function findByPriority(string $priority): array
    {
        return $this->createQueryBuilder('t')
            ->where('t.priority = :priority')
            ->setParameter('priority', $priority)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche des tâches par projet.
     * @param int $projetId
     * @return Tache[]
     */
    public function findByProjet(int $projetId): array
    {
        return $this->createQueryBuilder('t')
            ->join('t.projet', 'p')
            ->where('p.id = :projetId') // Correction ici
            ->setParameter('projetId', $projetId)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche des tâches par employé.
     * @param int $employeId
     * @return Tache[]
     */
    public function findByEmploye(int $employeId): array
    {
        return $this->createQueryBuilder('t')
            ->join('t.employe', 'e')
            ->where('e.idEmploye = :employeId') // Correction ici
            ->setParameter('employeId', $employeId)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche des tâches avec filtres multiples.
     * @param array $filters
     * @return Tache[]
     */
    public function searchTaches(array $filters = []): array
    {
        $qb = $this->createQueryBuilder('t');

        if (!empty($filters['status'])) {
            $qb->andWhere('t.status = :status')
                ->setParameter('status', $filters['status']);
        }

        if (!empty($filters['priority'])) {
            $qb->andWhere('t.priority = :priority')
                ->setParameter('priority', $filters['priority']);
        }

        if (!empty($filters['projet'])) {
            $qb->leftJoin('t.projet', 'p') // Utilisation de leftJoin pour éviter d'exclure certaines tâches
                ->andWhere('p.id = :projetId')
                ->setParameter('projetId', $filters['projet']);
        }

        if (!empty($filters['employe'])) {
            $qb->leftJoin('t.employe', 'e')
                ->andWhere('e.idEmploye = :employeId')
                ->setParameter('employeId', $filters['employe']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte le nombre de tâches par statut.
     * @return array
     */
    public function countTachesByStatus(): array
    {
        return $this->createQueryBuilder('t')
            ->select('t.status, COUNT(t) as count')
            ->groupBy('t.status')
            ->getQuery()
            ->getResult();
    }
}
