<?php

namespace App\Enum;

enum Role: string
{
    case RESPONSABLE_RH = 'responsable_rh';
    case CHEF_PROJET = 'chef_projet';
    case EMPLOYEE = 'employee';
    case CANDIDAT = 'candidat';

    public static function getValues(): array
    {
        return [
            self::RESPONSABLE_RH->value,
            self::CHEF_PROJET->value,
            self::EMPLOYEE->value,
            self::CANDIDAT->value
        ];
    }
}
