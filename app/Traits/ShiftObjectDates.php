<?php

namespace App\Traits;

use Illuminate\Support\Collection;
use Carbon\Carbon;
use DateTime;

trait ShiftObjectDates
{
  private function shiftObjectDates(object $item, array $fields, int $days): void
  {
    foreach ($fields as $field) {
      if (!isset($item->$field)) {
        continue;
      }

      $item->$field = (new DateTime($item->$field))
        ->modify(($days >= 0 ? '+' : '') . $days . ' day')
        ->format('Y-m-d');
    }
  }

  public function shiftOneDayBack(Collection|array $trend, string $period)
  {
    if ($period !== 'daily') {
      return $trend;
    }

    if ($trend instanceof Collection) {
      $trend->each(function ($item) {
        $this->shiftItemDay($item, -1);
      });

      return $trend;
    }

    foreach ($trend as &$item) {
      $this->shiftItemDay($item, -1);
    }
    unset($item);

    return $trend;
  }

  private function shiftItemDay(&$item, int $days): void
  {
    if (is_object($item) && isset($item->day)) {
      $item->day = Carbon::parse($item->day)->addDays($days)->format('Y-m-d');
    }

    if (is_array($item) && isset($item['day'])) {
      $item['day'] = Carbon::parse($item['day'])->addDays($days)->format('Y-m-d');
    }
  }

  public function shiftRangeByOneDayForward(array $range): array
  {
    foreach ($range as $item) {
      $this->shiftObjectDates($item, ['startDate', 'endDate'], 1);
    }

    return $range;
  }
}
