<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageGroup extends Model
{
  protected $fillable = ['factory', 'group_name'];

  public function packages()
  {
    return $this->belongsToMany(Package::class, 'package_group_members', 'group_id', 'package_id');
  }
}
