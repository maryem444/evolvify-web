<?php

namespace App\Entity;

enum StatutTache: string
{
    case TO_DO = 'TO_DO';
    case IN_PROGRESS = 'IN_PROGRESS';
    case DONE = 'DONE';
    case CANCELED = 'CANCELED';

    public function getLabel(): string
    {
        return match ($this) {
            self::TO_DO => 'À faire',
            self::IN_PROGRESS => 'En cours',
            self::DONE => 'Terminé',
            self::CANCELED => 'Annulé'
        };
    }
}
