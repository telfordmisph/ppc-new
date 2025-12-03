<?php

namespace App\Repositories;

use App\Traits\PackageAliasTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Helpers\SqlDebugHelper;

class PackageCapacityRepository
{
  use PackageAliasTrait;
  public const CAPACITY_TABLE = 'package_capacity_history';
  public function getLatestPackageCapacity($package, $factory)
  {
    return DB::table(self::CAPACITY_TABLE)
      ->where('package_name', $package)
      ->where('factory_name', $factory)
      ->orderByDesc('effective_from')
      ->first();
  }

  public function getPackageCapacity($packages, $factory)
  {

    // not stacking
    // $query = DB::table(self::CAPACITY_TABLE . ' as pch')
    //   ->where('factory_name', $factory);
    // $this->applyPackageAliasFilter($query, 'pch.package_name', $package, $factory);

    // Log::info(SqlDebugHelper::prettify($query->toSql(), $query->getBindings()));

    // return
    //   $query
    //   ->where('pch.factory_name', $factory)
    //   ->orderByDesc('pch.effective_from')
    //   ->get();

    // DB::raw('MAX(pch.effective_from) as latest_effective_from')

    $query = DB::table('package_capacity_history as pch')
      ->select([
        'pg.id as group_id',
        'pch.capacity',
        'pg.factory',
        'pch.package_name',
        'pch.effective_from',
        'pch.effective_to',
      ]);

    $this->applyPackageAliasFilter($query, 'pch.package_name', $packages, $factory);

    $query->where('pch.factory_name', $factory)
      ->groupBy('pg.id', 'pg.factory', 'pch.capacity', 'pch.effective_from', 'pch.effective_to', 'pch.package_name')
      ->distinct()
      ->orderByDesc('pch.effective_from');

    // Log::info(SqlDebugHelper::prettify($query->toSql(), $query->getBindings()));

    $filtered = collect($query->get())
      ->groupBy(fn($item) => $item->group_id . '_' . $item->effective_from)
      ->map(fn($groupItems) => $groupItems->first())
      ->values(); // reindex numerically

    return $filtered->all();
  }

  public function expandDaily($rows, $start, $end)
  {
    $result = [];

    foreach ($rows as $r) {
      $from = Carbon::parse($r->effective_from)->max($start);
      $to = $r->effective_to
        ? Carbon::parse($r->effective_to)->min($end)
        : $end;

      while ($from <= $to) {
        $result[] = [
          'day' => $from->toDateString(),
          'capacity' => $r->capacity
        ];
        $from->addDay();
      }
    }

    return $result;
  }


  public function get($id)
  {
    return DB::table('self::CAPACITY_TABLE')->where('id', $id)->first();
  }

  public function upsert($latest, $packageCapacity)
  {
    return DB::transaction(function () use ($packageCapacity, $latest) {
      $result = [
        'created' => [],
        'updated' => [],
      ];

      $existing = DB::table(self::CAPACITY_TABLE)
        ->where('package_name', $packageCapacity['package_name'])
        ->where('factory_name', $packageCapacity['factory_name'])
        ->where('effective_from', $packageCapacity['effective_from'])
        ->first();

      if ($existing) {
        DB::table(self::CAPACITY_TABLE)
          ->where('id', $existing->id)
          ->update([
            'capacity' => $packageCapacity['capacity'] ?? $existing->capacity,
          ]);

        $result['updated'][] = $packageCapacity;
      } else {
        if ($latest) {
          DB::table(self::CAPACITY_TABLE)
            ->where('id', $latest->id)
            ->update([
              'effective_to' => date('Y-m-d', strtotime($packageCapacity['effective_from'] . ' -1 day'))
            ]);
        }

        // Insert new row
        DB::table(self::CAPACITY_TABLE)->insert([
          'package_name'   => $packageCapacity['package_name'],
          'factory_name'   => $packageCapacity['factory_name'],
          'capacity'       => $packageCapacity['capacity'] ?? null,
          'effective_from' => $packageCapacity['effective_from'],
          'effective_to'   => null,
        ]);

        $result['created'][] = $packageCapacity;
      }

      return $result;
    });
  }


  public function update($latest, $id, $newPackageCapacityData)
  {
    return DB::transaction(function () use ($newPackageCapacityData, $latest, $id) {
      if ($latest) {
        // Log::info('latest: ' . print_r($latest, true));

        DB::table(self::CAPACITY_TABLE)
          ->where('id', $latest->id)
          ->update([
            'effective_to' => date('Y-m-d', strtotime($newPackageCapacityData['effective_from'] . ' -1 day'))
          ]);
      }

      DB::table(self::CAPACITY_TABLE)->where('id', $id)->update([
        'package_name'   => $newPackageCapacityData['package_name'],
        'factory_name'   => $newPackageCapacityData['factory_name'],
        'capacity'       => $newPackageCapacityData['capacity'] ?? null,
        'effective_from' => $newPackageCapacityData['effective_from'],
        'effective_to'   => $newPackageCapacityData['effective_to'] ?? null,
      ]);
    });
  }

  public function getSummaryLatestAndPrevious($factory)
  {
    // Base query
    $query = DB::table(self::CAPACITY_TABLE . ' as pch');

    $latestSub = DB::table(self::CAPACITY_TABLE . ' as sub')
      ->select('package_name', 'factory_name', DB::raw('MAX(effective_from) as latest_from'))
      ->groupBy('package_name', 'factory_name');

    $query->joinSub($latestSub, 'latest', function ($join) {
      $join->on('pch.package_name', '=', 'latest.package_name')
        ->on('pch.factory_name', '=', 'latest.factory_name')
        ->on('pch.effective_from', '=', 'latest.latest_from');
    });

    $previousCapacitySub = DB::table(self::CAPACITY_TABLE . ' as sub_prev')
      ->select('capacity')
      ->whereColumn('sub_prev.package_name', 'pch.package_name')
      ->whereColumn('sub_prev.factory_name', 'pch.factory_name')
      ->whereColumn('sub_prev.effective_from', '<', 'pch.effective_from')
      ->orderByDesc('sub_prev.effective_from')
      ->limit(1);

    $previousFromSub = DB::table(self::CAPACITY_TABLE . ' as sub_prev')
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

  public function upsertMultiple(array $data)
  {
    return DB::transaction(function () use ($data) {
      $result = [
        'created' => [],
        'updated' => [],
      ];

      $keys = array_map(fn($item) => [
        'package_name' => $item['package_name'],
        'factory_name' => $item['factory_name']
      ], $data);

      $uniqueKeys = array_map("unserialize", array_unique(array_map("serialize", $keys)));

      $latestMap = [];
      foreach ($uniqueKeys as $key) {
        $latestMap[$key['package_name'] . '|' . $key['factory_name']] =
          $this->getLatestPackageCapacity($key['package_name'], $key['factory_name']);
      }

      foreach ($data as $packageCapacity) {
        $key = $packageCapacity['package_name'] . '|' . $packageCapacity['factory_name'];
        $latest = $latestMap[$key] ?? null;

        $upsertResult = $this->upsert($latest, $packageCapacity);

        $result['created'] = array_merge($result['created'], $upsertResult['created']);
        $result['updated'] = array_merge($result['updated'], $upsertResult['updated']);
      }

      return $result;
    });
  }
}
