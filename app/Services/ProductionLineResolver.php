<?php

namespace App\Services;

use App\Constants\WipConstants;
use Illuminate\Support\Facades\DB;

class ProductionLineResolver
{
  public static function factoryFromFocusGroup(?string $focusGroup): ?string
  {
    if (!$focusGroup) return null;

    if (in_array($focusGroup, WipConstants::F2_OUT_FOCUS_GROUP_INCLUSION)) return 'F2';
    if (!in_array($focusGroup, WipConstants::FOCUS_GROUP_F1_EXCLUSION)) return 'F1';

    return null;
  }

  public function resolve(string $package, ?string $factory, ?int $leadCount, ?string $partName): ?string
  {
    $rule = DB::table('ppc_package_pl_rules')
      ->where('package', $package)
      ->where('is_active', 1)
      ->where(function ($q) use ($factory) {
        $q->whereNull('factory');
        if ($factory) {
          $q->orWhere('factory', $factory);
        }
      })
      ->where(function ($q) use ($leadCount) {
        $q->whereNull('lead_count');
        if ($leadCount !== null) {
          $q->orWhere('lead_count', $leadCount);
        }
      })
      ->where(function ($q) use ($partName) {
        $q->whereNull('partname_like');
        if ($partName) {
          $q->orWhereRaw('? LIKE partname_like', [$partName]);
        }
      })
      ->whereRaw('(valid_from IS NULL OR valid_from <= CURDATE())')
      ->whereRaw('(valid_to IS NULL OR valid_to >= CURDATE())')
      ->orderBy('priority')
      ->value('production_line');

    if ($rule) return $rule;

    return DB::table('ppc_package_master')
      ->where('package', $package)
      ->where('is_telford', 1)
      ->where('is_active', 1)
      ->value('default_pl');
  }
}
