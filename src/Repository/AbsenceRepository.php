<?php

namespace App\Repository;

use App\Entity\Absence;
use App\Entity\AbsenceStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Absence>
 *
 * @method Absence|null find($id, $lockMode = null, $lockVersion = null)
 * @method Absence|null findOneBy(array $criteria, array $orderBy = null)
 * @method Absence[]    findAll()
 * @method Absence[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AbsenceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Absence::class);
    }

    /**
     * Trouve les absences d'un employé spécifique
     */
    public function findByEmploye(int $idEmploye): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.idEmploye = :idEmploye')
            ->setParameter('idEmploye', $idEmploye)
            ->orderBy('a.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les absences par date
     */
    public function findByDate(\DateTime $date): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.date = :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les absences par statut
     */
    public function findByStatus(string $status): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.status = :status')
            ->setParameter('status', $status)
            ->orderBy('a.date', 'DESC')
            ->getQuery()
            ->getResult();
    }
    public function findAbsentEmployeesInPeriod(\DateTimeInterface $startDate, \DateTimeInterface $endDate)
{
    return $this->createQueryBuilder('a')
        ->select('DISTINCT a.idEmploye')
        ->where('a.date BETWEEN :startDate AND :endDate')
        ->andWhere('a.status = :absentStatus')
        ->setParameter('startDate', $startDate)
        ->setParameter('endDate', $endDate)
        ->setParameter('absentStatus', AbsenceStatus::ABSENT)
        ->getQuery()
        ->getResult();
}
}