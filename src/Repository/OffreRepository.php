<?php

namespace App\Repository;

use App\Entity\Offre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OffreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Offre::class);
    }

    public function search(string $keyword)
    {
    return $this->createQueryBuilder('o')
        ->where('LOWER(o.titre) LIKE :keyword')
        ->orWhere('LOWER(o.description) LIKE :keyword')
        ->orWhere('LOWER(o.status) LIKE :keyword') // Si le statut est une chaîne, on le filtre aussi
        ->orWhere('o.datePublication LIKE :keyword') // Vérifie aussi les dates (en texte brut ou au format d/m/Y)
        ->orWhere('o.dateExpiration LIKE :keyword')
        ->setParameter('keyword', '%' . strtolower($keyword) . '%')
        ->getQuery()
        ->getResult();
    }


}
