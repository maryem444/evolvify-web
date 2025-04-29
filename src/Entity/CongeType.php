<?php

namespace App\Entity;

enum CongeType: string
{
    case CONGE = 'conge';
    case TT = 'TT';
    case AUTORISATION = 'autorisation';

    public function getLabel(): string 
    {
        return match($this) {
            self::CONGE => 'Congé',
            self::TT => 'Télétravail',
            self::AUTORISATION => 'Autorisation'
        };
    }
}