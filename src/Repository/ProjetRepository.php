<?php

namespace App\Repository;

use App\Entity\Projet;
use App\Entity\StatutProjet;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

class ProjetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Projet::class);
    }

    /**
     * Récupère la liste de tous les projets
     * @return Projet[]
     */
    // Dans App\Repository\ProjetRepository.php
public function getProjetListQB(?User $user = null)
{
    $qb = $this->createQueryBuilder('p')
        ->leftJoin('p.assignedUsers', 'u')
        ->addSelect('u');

    // Si un utilisateur est fourni et n'est pas un chef de projet, filtrer par projets assignés
    if ($user !== null && !in_array('ROLE_CHEF_PROJET', $user->getRoles())) {
        $qb->andWhere(':user MEMBER OF p.assignedUsers')
           ->setParameter('user', $user);
    }

    return $qb->getQuery()->getResult();
}

    /**
     * Recherche des projets par statut
     * @param StatutProjet $status
     * @return Projet[]
     */
    public function findByStatus(StatutProjet $status)
    {
        return $this->createQueryBuilder('p')
            ->where('p.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getResult();
    }

    /**
     * Recherche de projets avec filtres multiples
     * @param array $filters
     * @return Projet[]
     */
    public function searchProjets(array $filters = [])
    {
        $qb = $this->createQueryBuilder('p');

        // Filtrage par statut
        if (isset($filters['status']) && $filters['status'] instanceof StatutProjet) {
            $qb->andWhere('p.status = :status')
                ->setParameter('status', $filters['status']);
        }

        // Filtrage par plage de dates
        if (isset($filters['start_date_from']) && $filters['start_date_from'] instanceof \DateTimeInterface) {
            $qb->andWhere('p.starterAt >= :start_date_from')
                ->setParameter('start_date_from', $filters['start_date_from']);
        }

        if (isset($filters['start_date_to']) && $filters['start_date_to'] instanceof \DateTimeInterface) {
            $qb->andWhere('p.starterAt <= :start_date_to')
                ->setParameter('start_date_to', $filters['start_date_to']);
        }

        // Filtrage par plage de dates de fin
        if (isset($filters['end_date_from']) && $filters['end_date_from'] instanceof \DateTimeInterface) {
            $qb->andWhere('p.endDate >= :end_date_from')
                ->setParameter('end_date_from', $filters['end_date_from']);
        }

        if (isset($filters['end_date_to']) && $filters['end_date_to'] instanceof \DateTimeInterface) {
            $qb->andWhere('p.endDate <= :end_date_to')
                ->setParameter('end_date_to', $filters['end_date_to']);
        }

        // Filtrage par nom (recherche partielle)
        if (isset($filters['name']) && !empty($filters['name'])) {
            $qb->andWhere('p.name LIKE :name')
                ->setParameter('name', '%' . $filters['name'] . '%');
        }

        // Filtrage par abbreviation
        if (isset($filters['abbreviation']) && !empty($filters['abbreviation'])) {
            $qb->andWhere('p.abbreviation = :abbreviation')
                ->setParameter('abbreviation', $filters['abbreviation']);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Compte le nombre de projets par statut
     * @return array
     */
    public function countProjetsByStatus()
    {
        return $this->createQueryBuilder('p')
            ->select('p.status, COUNT(p) as count')
            ->groupBy('p.status')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les projets actifs (en cours)
     * @return Projet[]
     */
    public function findActiveProjets()
    {
        return $this->createQueryBuilder('p')
            ->where('p.status = :status')
            ->setParameter('status', StatutProjet::IN_PROGRESS)
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère les projets récents (démarrés dans les X derniers jours)
     * @param int $days Nombre de jours en arrière
     * @return Projet[]
     */
    public function findRecentProjets(int $days = 30)
    {
        $date = new \DateTime();
        $date->modify("-{$days} days");

        return $this->createQueryBuilder('p')
            ->where('p.starterAt >= :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->getResult();
    }
    public function findProjectsWithDeadlineTomorrow(): array
    {
        $tomorrow = new \DateTime('tomorrow');
        $tomorrow->setTime(0, 0, 0);
        
        $endOfTomorrow = clone $tomorrow;
        $endOfTomorrow->setTime(23, 59, 59);
        
        return $this->createQueryBuilder('p')
            ->andWhere('p.endDate >= :start_tomorrow')
            ->andWhere('p.endDate <= :end_tomorrow')
            ->setParameter('start_tomorrow', $tomorrow)
            ->setParameter('end_tomorrow', $endOfTomorrow)
            ->getQuery()
            ->getResult();
    }

    
}
