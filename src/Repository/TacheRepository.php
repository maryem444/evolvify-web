<?php

namespace App\Repository;

use App\Entity\Tache;
use App\Entity\StatutTache;
use App\Entity\Priorite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

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
     * Récupère la liste de toutes les tâches
     * @return Tache[]
     */
    public function getTacheListQB()
    {
        return $this->createQueryBuilder('t')
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche des tâches par statut
     * @param StatutTache $status
     * @return Tache[]
     */
    public function findByStatus(StatutTache $status)
    {
        return $this->createQueryBuilder('t')
            ->where('t.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche des tâches par priorité
     * @param Priorite $priority
     * @return Tache[]
     */
    public function findByPriority(Priorite $priority)
    {
        return $this->createQueryBuilder('t')
            ->where('t.priority = :priority')
            ->setParameter('priority', $priority)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche des tâches par projet
     * @param int $projetId
     * @return Tache[]
     */
    public function findByProjet(int $projetId)
    {
        return $this->createQueryBuilder('t')
            ->where('t.id_projet = :projetId')
            ->setParameter('projetId', $projetId)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche des tâches par employé
     * @param int $employeId
     * @return Tache[]
     */
    public function findByEmploye(int $employeId)
    {
        return $this->createQueryBuilder('t')
            ->where('t.id_employe = :employeId')
            ->setParameter('employeId', $employeId)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche des tâches avec filtre multiple
     * @param array $filters
     * @return Tache[]
     */
    public function searchTaches(array $filters = [])
    {
        $qb = $this->createQueryBuilder('t');

        if (isset($filters['status']) && $filters['status'] instanceof StatutTache) {
            $qb->andWhere('t.status = :status')
               ->setParameter('status', $filters['status']);
        }

        if (isset($filters['priority']) && $filters['priority'] instanceof Priorite) {
            $qb->andWhere('t.priority = :priority')
               ->setParameter('priority', $filters['priority']);
        }

        if (isset($filters['projet'])) {
            $qb->andWhere('t.id_projet = :projetId')
               ->setParameter('projetId', $filters['projet']);
        }

        if (isset($filters['employe'])) {
            $qb->andWhere('t.id_employe = :employeId')
               ->setParameter('employeId', $filters['employe']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte le nombre de tâches par statut
     * @return array
     */
    public function countTachesByStatus()
    {
        return $this->createQueryBuilder('t')
            ->select('t.status, COUNT(t) as count')
            ->groupBy('t.status')
            ->getQuery()
            ->getResult();
    }
}