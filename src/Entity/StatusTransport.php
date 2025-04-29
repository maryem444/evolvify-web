<?php

namespace App\Entity;

enum StatusTransport: string 
{
    case DISPONIBLE = 'DISPONIBLE';
    case EN_MAINTENANCE = 'EN_MAINTENANCE';
    case EN_PANNE = 'EN_PANNE';
    
   
    public function getlabel(): string
    {
        return match($this) {
            self::DISPONIBLE => 'DISPONIBLE',
            self::EN_MAINTENANCE => 'EN_MAINTENANCE',
            self::EN_PANNE => 'EN_PANNE',
        };
    }
}