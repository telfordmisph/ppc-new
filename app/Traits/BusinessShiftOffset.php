<?php

namespace App\Traits;

use Carbon\Carbon;

trait BusinessShiftOffset
{
  public function translateToBusinessRange($startDate, $endDate): array
  {
    $start = Carbon::parse($startDate)
      ->startOfDay()
      ->addHours(6);

    $end = Carbon::parse($endDate)
      ->startOfDay()
      ->addDay()
      ->addHours(6);

    return [$start, $end];
  }
}
