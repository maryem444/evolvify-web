<?php

namespace App\Repository;

use App\Entity\Conge;
use App\Entity\CongeStatus;
use App\Entity\CongeType;
use App\Entity\CongeReason;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Conge>
 *
 * @method Conge|null find($id, $lockMode = null, $lockVersion = null)
 * @method Conge|null findOneBy(array $criteria, array $orderBy = null)
 * @method Conge[]    findAll()
 * @method Conge[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CongeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Conge::class);
    }

    /**
     * Find all conges with employee data preloaded
     * 
     * @return Conge[]
     */
    public function findAllWithEmployees()
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.employe', 'e')
            ->addSelect('e')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find conges for a specific employee
     * 
     * @param User $user
     * @return Conge[]
     */
    public function findByEmployee(User $user)
    {
        return $this->createQueryBuilder('c')
            ->where('c.employe = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find conges for a specific employee by ID
     * 
     * @param int $userId
     * @return Conge[]
     */
    public function findByEmployeeId(int $userId)
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.employe', 'e')
            ->where('e.idEmploye = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find a specific conge by ID with employee data preloaded
     * 
     * @param int $id
     * @return Conge|null
     */
    public function findOneWithEmployee(int $id): ?Conge
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.employe', 'e')
            ->addSelect('e')
            ->where('c.idConge = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
    
   /**
 * Trouve les congés avec filtres et pagination
 * 
 * @param string|null $searchTerm Terme de recherche pour le nom/prénom d'employé
 * @param CongeStatus|null $status Filtre par statut du congé
 * @param CongeType|null $type Filtre par type de congé
 * @param CongeReason|null $reason Filtre par raison du congé
 * @param int $page Numéro de page courant
 * @param int $limit Nombre d'éléments par page
 * @return array Tableau contenant les résultats et le nombre total d'éléments
 */
public function findFilteredAndPaginated(
    ?string $searchTerm = null,
    ?CongeStatus $status = null,
    ?CongeType $type = null,
    ?CongeReason $reason = null,
    int $page = 1,
    int $limit = 10
): array {
    $queryBuilder = $this->createQueryBuilder('c')
        ->leftJoin('c.employe', 'e')
        ->addSelect('e');
    
    // Filtre par nom ou prénom d'employé
    if ($searchTerm) {
        $queryBuilder->andWhere('LOWER(e.firstname) LIKE LOWER(:searchTerm) OR LOWER(e.lastname) LIKE LOWER(:searchTerm)')
            ->setParameter('searchTerm', '%' . strtolower($searchTerm) . '%');
    }
    
    // Filtre par statut
    if ($status) {
        $queryBuilder->andWhere('c.statusString = :status')
            ->setParameter('status', $status->value);
    }
    
    // Filtre par type
    if ($type) {
        $queryBuilder->andWhere('c.typeString = :type')
            ->setParameter('type', $type->value);
    }
    
    // Filtre par raison
    if ($reason) {
        $queryBuilder->andWhere('c.reasonString = :reason')
            ->setParameter('reason', $reason->value);
    }
    
    // Compter le nombre total d'éléments pour la pagination
    $countQueryBuilder = clone $queryBuilder;
    $totalItems = $countQueryBuilder->select('COUNT(c.idConge)')
        ->getQuery()
        ->getSingleScalarResult();
    
    // Appliquer la pagination
    $queryBuilder->setFirstResult(($page - 1) * $limit)
        ->setMaxResults($limit)
        ->orderBy('c.leaveStart', 'DESC');
    
    return [
        'results' => $queryBuilder->getQuery()->getResult(),
        'totalItems' => $totalItems
    ];
}
}