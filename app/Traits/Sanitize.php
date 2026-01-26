<?php

namespace App\Traits;

trait Sanitize
{
  protected function normalizeMonthNames(string $value): string
  {
    $map = [
      'junuary' => 'january',
      'januray' => 'january',
      'febuary' => 'february',
      'feburary' => 'february',
      'agust' => 'august',
      'sept' => 'september',
      'octuber' => 'october',
      'novemeber' => 'november',
      'decemeber' => 'december',
    ];

    $lower = strtolower($value);

    foreach ($map as $wrong => $correct) {
      if (str_contains($lower, $wrong)) {
        $value = str_ireplace($wrong, $correct, $value);
      }
    }

    return $value;
  }
}
