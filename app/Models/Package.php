<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
  protected $fillable = ['package_name'];

  public function groups()
  {
    return $this->belongsToMany(PackageGroup::class, 'package_group_members', 'package_id', 'group_id');
  }
}
