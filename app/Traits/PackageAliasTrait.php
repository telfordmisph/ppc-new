<?php


namespace App\Traits;

trait PackageAliasTrait
{
  //   Table: packages
  // Columns:
  // id int AI PK 
  // package_name varchar(45)

  // Table: package_groups
  // Columns:
  // id int AI PK 
  // factory varchar(20) 
  // group_name varchar(45)

  // Table: package_group_members
  // Columns:
  // package_id int PK 
  // group_id int PK

  protected $PACKAGES_TABLE = 'packages';
  protected $PACKAGE_GROUP_MEMBERS_TABLE = 'package_group_members';

  /**
   * Returns a closure for filtering a query by package aliases
   *
   * @param $query
   * @param string $column The column to match against packages (e.g. 'f3_pkg.package_name')
   * @param array|string $packageNames Package aliases to filter
   * @param string $factory
   */
  public function applyPackageAliasFilter($query, string $column, array|string $packageNames, $factory = null)
  {
    $query->join('packages as p', 'p.package_name', '=', $column)
      ->join('package_group_members as pgm', 'pgm.package_id', '=', 'p.id')
      ->join('package_group_members as pgm_input', 'pgm.group_id', '=', 'pgm_input.group_id')
      ->join('packages as p_input', 'pgm_input.package_id', '=', 'p_input.id')
      ->join('package_groups as pg', 'pgm.group_id', '=', 'pg.id')
      ->when($factory !== null, function ($q) use ($factory) {
        $q->where('pg.factory', $factory);
      })
      ->whereIn('p_input.package_name', $packageNames);
  }
  //unknown column p_input lol
}
// SELECT *
// FROM (
//     SELECT 
//         pch.*,
//         pg.id AS group_id,

//         ROW_NUMBER() OVER (
//             PARTITION BY pg.id
//             ORDER BY pch.effective_from DESC
//         ) AS rn
// FROM package_capacity_history AS pch
// JOIN packages AS p ON p.package_name = pch.package_name
// JOIN package_group_members AS pgm ON pgm.package_id = p.id
// JOIN package_group_members AS pgm_input
//   ON pgm_input.group_id = pgm.group_id
// JOIN packages AS p_input
//   ON p_input.id = pgm_input.package_id
// JOIN package_groups AS pg
//   ON pg.id = pgm.group_id
// WHERE pg.factory = 'f3'
//   AND p_input.package_name IN ('lga', 'ssop', '240 mils')
//   and pch.factory_name = 'f3'
//   ) t
// where t.rn = 1
// ORDER BY t.effective_from DESC;
