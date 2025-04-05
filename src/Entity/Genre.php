<?php

namespace App\Entity;

enum Genre: string
{
    case HOMME = 'HOMME';
    case FEMME = 'FEMME';

    public static function getValues(): array
    {
        return [
            self::HOMME->value,
            self::FEMME->value
        ];
    }
}
