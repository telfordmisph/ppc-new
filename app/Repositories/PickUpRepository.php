<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class PickUpRepository
{
  private const TABLE_NAME = 'ppc_pickupdb';
  private const PART_NAME_TABLE = 'ppc_partnamedb';

  public function getTotalQuantity($startDate, $endDate)
  {
    return DB::table(self::TABLE_NAME)
      ->whereBetween('DATE_CREATED', [$startDate, $endDate])
      ->sum('QTY');
  }

  public function getFactoryTotalQuantity($factory, $startDate, $endDate)
  {
    return DB::table(self::TABLE_NAME . ' as pickup')
      ->joinSub(
        DB::table(self::PART_NAME_TABLE)
          ->selectRaw('Partname, Factory')
          ->distinct(),
        'part',
        function ($join) {
          $join->on('pickup.PARTNAME', '=', 'part.Partname');
        }
      )
      ->whereBetween('pickup.DATE_CREATED', [$startDate, $endDate])
      ->where('part.Factory', $factory)
      ->sum('pickup.QTY');
  }

  public function getFactoryPlTotalQuantity($factory, $pl, $startDate, $endDate)
  {
    $partSubquery = DB::table(self::PART_NAME_TABLE)
      ->selectRaw('Partname, Factory, PL')
      ->distinct();

    return DB::table(self::TABLE_NAME . ' as pickup')
      ->joinSub($partSubquery, 'part', function ($join) {
        $join->on('pickup.PARTNAME', '=', 'part.Partname');
      })
      ->whereBetween('pickup.DATE_CREATED', [$startDate, $endDate])
      ->where('part.Factory', $factory)
      ->where('part.PL', $pl)
      ->sum('pickup.QTY');
  }

  public function getPackageSummary($chartStatus, $startDate, $endDate)
  {
    $query = DB::table(self::TABLE_NAME . ' as pickup')
      ->selectRaw('pickup.PACKAGE, SUM(pickup.QTY) as total_quantity, COUNT(DISTINCT pickup.LOTID) as total_lots')
      ->whereBetween('pickup.DATE_CREATED', [$startDate, $endDate]);

    switch ($chartStatus) {
      case 'F1':
      case 'F2':
      case 'F3':
        $query->join(self::PART_NAME_TABLE . ' as part', 'pickup.PARTNAME', '=', 'part.Partname')
          ->where('part.Factory', $chartStatus);
        break;

      case 'PL1':
      case 'PL6':
        $query->join(self::PART_NAME_TABLE . ' as part', 'pickup.PARTNAME', '=', 'part.Partname')
          ->where('part.PL', $chartStatus);
        break;

      case 'all':
        break;

      default:
        $query->whereRaw('DATE(pickup.DATE_CREATED) = CURDATE()');
        break;
    }

    return $query->groupBy('pickup.PACKAGE')
      ->orderByDesc('total_quantity')
      ->get();
  }
}
