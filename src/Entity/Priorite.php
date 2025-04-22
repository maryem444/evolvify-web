<?php

namespace App\Entity;

enum Priorite: string
{
  case LOW = 'LOW';
  case MEDIUM = 'MEDIUM';
  case HIGH = 'HIGH';

  public function getLabel(): string
  {
    return match ($this) {
      self::LOW => 'Basse',
      self::MEDIUM => 'Moyenne',
      self::HIGH => 'Haute'
    };
  }
}
