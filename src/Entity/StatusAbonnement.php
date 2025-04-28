<?php

namespace App\Entity;

enum StatusAbonnement: string 
{
    case ACTIF = 'ACTIF';
    case EXPIRE = 'EXPIRE';
    case SUSPENDU = 'SUSPENDU';

    // Exemple d'ajout d'une méthode à l'énumération
    public function getlabel(): string
    {
        return match($this) {
            self::ACTIF => 'ACTIF',
            self::EXPIRE => 'EXPIRE',
            self::SUSPENDU => 'SUSPENDU',
        };
    }
}
