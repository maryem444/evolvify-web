<?php

namespace App\Entity;

enum AbsenceStatus: string
{
    case ABSENT = 'ABSENT';
    case PRESENT = 'PRESENT';
    case EN_CONGE = 'EN_CONGE';
    case EMPTY = '';
    
    public function getLabel(): string
    {
        return match($this) {
            self::ABSENT => 'Absent',
            self::PRESENT => 'Présent',
            self::EN_CONGE => 'En congé',
            self::EMPTY => '',
        };
    }
    
    public static function getValues(): array
    {
        return array_column(self::cases(), 'value');
    }
}