<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class F3Pickup extends Model
{
  protected $table = 'f3_pickup';
  public $timestamps = false;
  protected $primaryKey = 'id_pickup';

  protected $fillable = [
    'PARTNAME',
    'LOTID',
    'QTY',
    'PACKAGE',
  ];
}
