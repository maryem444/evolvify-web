<?php

namespace App\Repository;

use App\Entity\UserBiometricData;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserBiometricData>
 */
class UserBiometricDataRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserBiometricData::class);
    }
    
    public function findByUser(User $user): ?UserBiometricData
    {
        return $this->findOneBy(['user' => $user]);
    }
}