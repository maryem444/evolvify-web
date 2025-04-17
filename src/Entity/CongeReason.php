<?php

namespace App\Entity;

enum CongeReason: string
{
    case CONGE_PAYE = 'CONGE_PAYE';
    case SANS_SOLDE = 'SANS_SOLDE';
    case MALADIE = 'MALADIE';

    public function getLabel(): string 
    {
        return match($this) {
            self::CONGE_PAYE => 'Congé payé',
            self::SANS_SOLDE => 'Congé sans solde',
            self::MALADIE => 'Arrêt maladie'
        };
    }
}