<?php

namespace App\Entity;

enum StatusEntretien: string
{
    case EN_COURS = 'en_cours';
    case REFUSE = 'refuse';
    case ACCEPTE = 'accepte';

    public static function getValues(): array
    {
        return [
            self::EN_COURS->value,
            self::REFUSE->value,
            self::ACCEPTE->value
        ];
    }

}
