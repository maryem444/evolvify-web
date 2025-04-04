<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    // Vous pouvez ajouter des méthodes spécifiques pour récupérer les utilisateurs
    public function findActiveUsers()
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.isActive = :val')
            ->setParameter('val', true)
            ->getQuery()
            ->getResult();
    }
}
