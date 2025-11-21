<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class PackageCapacityRepository
{
  public const CAPACITY_TABLE = 'package_capacity_history';
  public function getLatestPackageCapacity($package, $factory)
  {
    return DB::table(self::CAPACITY_TABLE)
      ->where('package_name', $package)
      ->where('factory_name', $factory)
      ->orderByDesc('effective_from')
      ->first();
  }

  public function get($id)
  {
    return DB::table('package_capacity_history')->where('id', $id)->first();
  }

  public function insert($latest, $packageCapacity)
  {
    return DB::transaction(function () use ($packageCapacity, $latest) {
      if ($latest) {
        DB::table('package_capacity_history')
          ->where('id', $latest->id)
          ->update([
            'effective_to' => date('Y-m-d', strtotime($packageCapacity['effective_from'] . ' -1 day'))
          ]);
      }

      DB::table('package_capacity_history')->insert([
        'package_name'   => $packageCapacity['package_name'],
        'factory_name'   => $packageCapacity['factory_name'],
        'capacity'       => $packageCapacity['capacity'] ?? null,
        'effective_from' => $packageCapacity['effective_from'],
        'effective_to'   => null,
      ]);
    });
  }

  public function update($latest, $id, $packageCapacity)
  {
    return DB::transaction(function () use ($packageCapacity, $latest, $id) {
      if ($latest) {
        DB::table('package_capacity_history')
          ->where('id', $latest->id)
          ->update([
            'effective_to' => date('Y-m-d', strtotime($packageCapacity['effective_from'] . ' -1 day'))
          ]);
      }

      DB::table('package_capacity_history')->where('id', $id)->update([
        'package_name'   => $packageCapacity['package_name'],
        'factory_name'   => $packageCapacity['factory_name'],
        'capacity'       => $packageCapacity['capacity'] ?? null,
        'effective_from' => $packageCapacity['effective_from'],
        'effective_to'   => $packageCapacity['effective_to'] ?? null,
      ]);
    });
  }

  public function getSummaryLatestAndPrevious($factory)
  {
    // Base query
    $query = DB::table('package_capacity_history as pch');

    $latestSub = DB::table('package_capacity_history as sub')
      ->select('package_name', 'factory_name', DB::raw('MAX(effective_from) as latest_from'))
      ->groupBy('package_name', 'factory_name');

    $query->joinSub($latestSub, 'latest', function ($join) {
      $join->on('pch.package_name', '=', 'latest.package_name')
        ->on('pch.factory_name', '=', 'latest.factory_name')
        ->on('pch.effective_from', '=', 'latest.latest_from');
    });

    $previousCapacitySub = DB::table('package_capacity_history as sub_prev')
      ->select('capacity')
      ->whereColumn('sub_prev.package_name', 'pch.package_name')
      ->whereColumn('sub_prev.factory_name', 'pch.factory_name')
      ->whereColumn('sub_prev.effective_from', '<', 'pch.effective_from')
      ->orderByDesc('sub_prev.effective_from')
      ->limit(1);

    $previousFromSub = DB::table('package_capacity_history as sub_prev')
      ->select('effective_from')
      ->whereColumn('sub_prev.package_name', 'pch.package_name')
      ->whereColumn('sub_prev.factory_name', 'pch.factory_name')
      ->whereColumn('sub_prev.effective_from', '<', 'pch.effective_from')
      ->orderByDesc('sub_prev.effective_from')
      ->limit(1);

    $query->addSelect([
      'pch.package_name',
      'pch.factory_name',
      'pch.capacity as latest_capacity',
      'pch.effective_from as latest_from',
      DB::raw('(' . $previousCapacitySub->toSql() . ') as previous_capacity'),
      DB::raw('(' . $previousFromSub->toSql() . ') as previous_from')
    ]);

    $query->where('pch.factory_name', $factory);

    return $query->get();
  }
}
