<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\F3RawPackage;

class F3Pickup extends Model
{
  protected $table = 'f3_pickup';
  public $timestamps = false;

  protected $fillable = [
    'ppc_pickup_id',
  ];
}
