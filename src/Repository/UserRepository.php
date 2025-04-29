<?php
// src/Repository/UserRepository.php
namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', get_class($user)));
        }

        $user->setPassword($newHashedPassword);
        $this->_em->persist($user);
        $this->_em->flush();
    }

    /**
     * Count users by role
     * 
     * @return array
     */
    public function countUsersByRole(): array
    {
        $users = $this->findAll();
        $usersByRole = [];
        
        foreach ($users as $user) {
            foreach ($user->getRoles() as $role) {
                // Skip default role if you want
                if ($role === 'ROLE_USER') continue;
                
                if (!isset($usersByRole[$role])) {
                    $usersByRole[$role] = ['role' => $role, 'count' => 0];
                }
                $usersByRole[$role]['count']++;
            }
        }
        
        return array_values($usersByRole);
    }
    
    /**
     * Count total users
     * 
     * @return int
     */
    public function countTotal(): int
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
    
    /**
     * Count users by gender
     * 
     * @param string $gender
     * @return int
     */
    public function countByGender(string $gender): int
    {
        return $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.gender = :gender')
            ->setParameter('gender', $gender)
            ->getQuery()
            ->getSingleScalarResult();
    }
    
    /**
     * Get users with leave balance
     * 
     * @return array
     */
    public function getUsersWithLeaveBalance(): array
    {
        return $this->createQueryBuilder('u')
            ->select('u.firstname, u.lastname, u.conge_restant')
            ->orderBy('u.conge_restant', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Get leave balances
     * 
     * @return array
     */
    public function getLeaveBalances(): array
    {
        return $this->createQueryBuilder('u')
            ->select('u.firstname, u.lastname, u.conge_restant')
            ->orderBy('u.conge_restant', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();
    }
}