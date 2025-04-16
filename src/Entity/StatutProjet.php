<?php
namespace App\Entity;

enum StatutProjet: string 
{
    case IN_PROGRESS = 'IN_PROGRESS';
    case COMPLETED = 'COMPLETED';

    public function getLabel(): string
    {
        return match($this) {
            self::IN_PROGRESS => 'En cours',
            self::COMPLETED => 'Terminé'
        };
    }
}