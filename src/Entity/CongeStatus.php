<?php

namespace App\Entity;

enum CongeStatus: string
{
    case REFUSE = 'REFUSE';
    case ACCEPTE = 'ACCEPTE';
    case EN_COURS = 'EN_COURS';
    case EMPTY = '';

    public function getLabel(): string 
    {
        return match($this) {
            self::REFUSE => 'Refusé',
            self::ACCEPTE => 'Accepté',
            self::EN_COURS => 'En cours',
            self::EMPTY => 'Vide'
        };
    }
}