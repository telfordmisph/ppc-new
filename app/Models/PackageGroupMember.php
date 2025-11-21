<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PackageGroupMember extends Pivot
{
  protected $table = 'package_group_members';
  protected $fillable = ['package_id', 'group_id'];
}
