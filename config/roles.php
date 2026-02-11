<?php

$permission_sets = [
  'basic_f3' => ['f3_read', 'f3_edit'],
  'f3_full' => ['f3_read', 'f3_edit', 'f3_delete', 'f3_insert'],

  'machine_mutate' => [
    'machine_mutate',
    // 'machine_edit', 
    // 'machine_delete', 
    // 'machine_insert'
  ],

  'f3_mutate' => [
    'f3_mutate',
    // 'f3_edit', 
    // 'f3_delete', 
    // 'f3_insert'
  ],

  'body_size_mutate' => [
    'body_size_mutate',
    // 'body_size_edit', 
    // 'body_size_delete', 
    // 'body_size_insert'
  ],

  'body_size_capacity_mutate' => [
    'body_size_capacity_mutate',
    // 'body_size_capacity_edit', 
    // 'body_size_capacity_delete', 
    // 'body_size_capacity_insert'
  ],

  // faulty
  'f3_limited' => ['f3_read', 'f3_edit_remarks', 'f3_edit_status'],

  'package_group_mutate' => [
    'package_group_mutate'
    // 'package_group_delete',
    // 'package_group_insert',
    // 'package_group_edit'
  ],

  'f3_package_mutate' => [
    'f3_package_mutate'
    // 'f3_package_delete',
    // 'f3_package_insert',
    // 'f3_package_edit',
  ],

  'f3_raw_package_mutate' => [
    'f3_raw_package_mutate'
    // 'f3_raw_package_delete',
    // 'f3_raw_package_insert',
    // 'f3_raw_package_edit',
  ],

  'partname_mutate' => [
    'partname_mutate',
    // 'partname_delete',
    // 'partname_insert',
    // 'partname_edit',
  ],

  'package_management' => [
    'package_group_read',
    'package_group_delete',
    'package_group_insert',
    'package_group_edit',
    'f3_raw_package_read',
    'f3_raw_package_delete',
    'f3_raw_package_insert',
    'f3_raw_package_edit',
    'partname_read',
    'partname_delete',
    'partname_insert',
    'partname_edit',
    'f3_package_read',
    'f3_package_delete',
    'f3_package_insert',
    'f3_package_edit',
  ],

  'import_data' => ['import_f1f2_wip', 'import_f1f2_out', 'import_f3', 'import_pickup', 'import_capacity'],
  'import_data_all' => ['import_data_all'],

  'import_data_no_f3' => ['import_f1f2_wip', 'import_f1f2_out'],
  'trend_access' => [
    'wip_out_capacity_trend',
    'pickup_trend',
    'residual_trend',
    'wip_trend',
    'f1f2_station_trend'
  ],
  'dashboard' => ['dashboard'],
  'wip_station' => ['wip_station'],
  'body_size' => ['body_size'],
  'capacity' => ['capacity_read', 'capacity_upload'],
];

$full_access = array_merge(
  $permission_sets['import_data'],
  $permission_sets['trend_access'],
  $permission_sets['package_management'],
  $permission_sets['f3_full'],
  $permission_sets['f3_mutate'],
  $permission_sets['capacity'],
  $permission_sets['dashboard'],
  $permission_sets['wip_station'],
  $permission_sets['body_size'],
  $permission_sets['package_group_mutate'],
  $permission_sets['f3_raw_package_mutate'],
  $permission_sets['partname_mutate'],
  $permission_sets['f3_package_mutate'],
  $permission_sets['import_data_all'],
  $permission_sets['machine_mutate'],
  $permission_sets['body_size_mutate'],
  $permission_sets['body_size_capacity_mutate'],
);

$full_access_except_f3_import = array_merge(
  $permission_sets['import_data_no_f3'],
  $permission_sets['trend_access'],
  $permission_sets['package_management'],
  $permission_sets['f3_full'],
  $permission_sets['f3_mutate'],
  $permission_sets['capacity'],
  $permission_sets['dashboard'],
  $permission_sets['wip_station'],
  $permission_sets['body_size'],
  $permission_sets['package_group_mutate'],
  $permission_sets['f3_raw_package_mutate'],
  $permission_sets['partname_mutate'],
  $permission_sets['f3_package_mutate'],
  $permission_sets['import_data_all'],
  $permission_sets['machine_mutate'],
  $permission_sets['body_size_mutate'],
  $permission_sets['body_size_capacity_mutate'],
);

return [
  'Production Supervisor' => $full_access,
  'Senior Production Supervisor' => $full_access,
  'Production Section Head' => $full_access,
  'Section Head' => $full_access,

  'programmer 1' => $full_access,

  'PPC Manager' => $full_access,
  'Trainee PPC Planner' => $full_access,
  'PPC' => $full_access,
  'PPC Engineer' => $full_access,
  'PPC Planner' => $full_access,
  'PPC Planner 2' => $full_access,
  'PPC Expediter 1' => $full_access,
  'PPC Expediter 2' => $full_access,
  'Planner 2' => $full_access,
  'Planner' => $full_access,
  'PPC Senior Supervisor' => $full_access,
  'ppc supervisor' => $full_access,
  'Residual Controller 1' => $full_access,

  // 'programmer 1' => $full_access,

  'user' => [
    'view_trends',
    'edit_remarks',
  ],
];
