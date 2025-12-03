<?php

namespace App\Traits;

use Illuminate\Support\Facades\DB;

trait FilterPackageTrait
{
  public function doestExist($column, $value, $table = null)
  {
    return !DB::table($table)
      ->where($column, $value)
      ->exists();
  }
}
