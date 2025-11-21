<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class F3RawPackage extends Model
{
  protected $table = 'f3_raw_packages';
  public $timestamps = false;

  protected $fillable = [
    'raw_package',
    'lead_count',
    'package_id',
    'dimension',
  ];

  public static function getTableName()
  {
    return (new static)->getTable();
  }

  public function f3_package_name()
  {
    return $this->belongsTo(F3PackageName::class, 'package_id');
  }
}
