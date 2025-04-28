<?php

namespace App\Entity;

enum Role: string
{
    case RESPONSABLE_RH = 'RESPONSABLE_RH';
    case CHEF_PROJET = 'CHEF_PROJET';
    case EMPLOYEE = 'EMPLOYEE';
    case CONDIDAT = 'CONDIDAT';

    public static function getValues(): array
    {
        return [
            self::RESPONSABLE_RH->value,
            self::CHEF_PROJET->value,
            self::EMPLOYEE->value,
            self::CONDIDAT->value
        ];
    }
}
