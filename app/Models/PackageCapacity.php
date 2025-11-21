<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageCapacity extends Model
{
  protected $table = 'package_capacity_history';
  protected $fillable = [
    'package_name',
    'factory_name',
    'capacity',
    'effective_from',
  ];

  public $timestamps = false;
}
