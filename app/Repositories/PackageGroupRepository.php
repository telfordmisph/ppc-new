<?php

namespace App\Repositories;

use Illuminate\Container\Attributes\Log;
use Illuminate\Support\Facades\DB;

class PackageGroupRepository
{
  public function saveGroup(?int $groupId, string $factory, $groupName, array $packageNames)
  {
    $normalized = array_map(fn($v) => trim(strtolower($v)), $packageNames);

    if (count($normalized) !== count(array_unique($normalized))) {
      throw new \InvalidArgumentException('Invalid: Duplicate package names.');
    }

    DB::transaction(function () use ($groupId, $factory, $groupName, $packageNames) {

      if ($groupId) {
        // Update existing group
        DB::table('package_groups')
          ->where('id', $groupId)
          ->update([
            'factory' => $factory,
            'group_name' => $groupName
          ]);
      } else {
        $groupId = DB::table('package_groups')->insertGetId([
          'factory' => $factory,
          'group_name' => $groupName
        ]);
      }

      $packageIds = [];
      foreach ($packageNames as $name) {
        $package = DB::table('packages')->where('package_name', $name)->first();
        if (!$package) {
          $packageIds[] = DB::table('packages')->insertGetId(['package_name' => $name]);
        } else {
          $packageIds[] = $package->id;
        }
      }

      DB::table('package_group_members')
        ->where('group_id', $groupId)
        ->delete();

      $insertData = array_map(fn($pid) => ['package_id' => $pid, 'group_id' => $groupId], $packageIds);
      DB::table('package_group_members')->insert($insertData);
    });

    return $groupId;
  }

  public function getMembersByPackageName(array|string $packageNames, array|string $factories)
  {
    $packageNames = is_array($packageNames) ? $packageNames : [$packageNames];
    $factories = is_array($factories) ? $factories : [$factories];

    $members = DB::table('packages as p_input')
      ->join('package_group_members as pgm_input', 'pgm_input.package_id', '=', 'p_input.id')
      ->join('package_group_members as pgm_group', 'pgm_group.group_id', '=', 'pgm_input.group_id')
      ->join('packages as p_member', 'p_member.id', '=', 'pgm_group.package_id')
      ->join('package_groups as pg', 'pg.id', '=', 'pgm_input.group_id')
      ->whereIn('p_input.package_name', $packageNames)
      ->whereIn('pg.factory', $factories)
      ->distinct()
      ->pluck('p_member.package_name');

    return $members;
  }

  public function getUniquePackageNamesByFactory(array|string $factories)
  {
    $factories = is_array($factories) ? $factories : [$factories];

    return DB::table('packages as p')
      ->join('package_group_members as pgm', 'pgm.package_id', '=', 'p.id')
      ->join('package_groups as pg', 'pg.id', '=', 'pgm.group_id')
      ->whereIn('pg.factory', $factories)
      ->distinct()
      ->pluck('p.package_name')
      ->toArray();
  }

  public function separateByGroups($packageNames, $factory)
  {
    $groups = [];

    // $rows = DB::table('packages as p_input')
    //   ->join('package_group_members as pgm_input', 'pgm_input.package_id', '=', 'p_input.id')
    //   ->join('package_group_members as pgm_group', 'pgm_group.group_id', '=', 'pgm_input.group_id')
    //   ->join('packages as p_member', 'p_member.id', '=', 'pgm_group.package_id')
    //   ->join('package_groups as pg', 'pg.id', '=', 'pgm_input.group_id')
    //   ->whereIn('p_input.package_name', $packageNames)
    //   ->whereIn('pg.factory', $factory)
    //   ->select('pg.factory', 'pgm_group.group_id', 'p_member.package_name')
    //   ->get();

    $rows = DB::table('packages as p_input')
      ->leftJoin('package_group_members as pgm_input', 'pgm_input.package_id', '=', 'p_input.id')
      ->leftJoin('package_group_members as pgm_group', 'pgm_group.group_id', '=', 'pgm_input.group_id')
      ->leftjoin('packages as p_member', 'p_member.id', '=', 'pgm_group.package_id')
      ->leftJoin('package_groups as pg', 'pg.id', '=', 'pgm_input.group_id')
      ->whereIn('p_input.package_name', $packageNames)
      ->where(function ($q) use ($factory) {
        $q->whereNull('pg.factory')->orWhereIn('pg.factory', $factory);
      })
      ->select('pg.factory', 'pgm_group.group_id', 'p_member.package_name')
      ->get();


    return $rows
      ->groupBy('group_id')
      ->map(fn($items) => $items->pluck('package_name')->all())
      ->toArray();
  }
}
