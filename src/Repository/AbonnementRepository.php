<?php

namespace App\Repository;

use App\Entity\Abonnement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Abonnement>
 */
class AbonnementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Abonnement::class);
    }

    /**
     * Récupère la liste de tous les abonnements
     * @return Abonnement[] 
     */
    public function findAllAbonnement()
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.id_Ab', 'ASC')  // Changez 'id' par 'id_abonnement'
            ->getQuery()
            ->getResult();
    }
    
}
