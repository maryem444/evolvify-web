<?php

namespace App\Enum;

enum Genre: string
{
    case HOMME = 'homme';
    case FEMME = 'femme';

    public static function getValues(): array
    {
        return [
            self::HOMME->value,
            self::FEMME->value
        ];
    }
}
