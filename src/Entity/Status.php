<?php

namespace App\Entity;


enum Status: string
{
    case ACTIVE = 'active';
    case EXPIRE = 'expire';
    case ATTEND = 'attend';

    public static function getValues(): array
    {
        return [
            self::ACTIVE->value,
            self::EXPIRE->value,
            self::ATTEND->value,
        ];
    }

    public static function getChoices(): array
    {
        return [
            'Active' => self::ACTIVE->value,
            'Expire' => self::EXPIRE->value,
            'Attend' => self::ATTEND->value,
        ];
    }
}

