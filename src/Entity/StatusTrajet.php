<?php

namespace App\Entity;

enum StatusTrajet: string 
{
    case PLANIFIE = 'PLANIFIE';
    case EN_COURS = 'EN_COURS';
    case TERMINE = 'TERMINE';
    case ANNULE = 'ANNULE';
    // Exemple d'ajout d'une méthode à l'énumération
    public function getlabel(): string
    {
        return match($this) {
            self::PLANIFIE => 'PLANIFIE',
            self::EN_COURS => 'EN_COURS',
            self::TERMINE => 'TERMINE',
            self::ANNULE => 'ANNULE',
        };
    }
}