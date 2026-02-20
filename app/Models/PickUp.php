<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PickUp extends Model
{
  use HasFactory;

  protected $table = 'ppc_pickupdb';

  protected $primaryKey = 'id_pickup';

  protected $fillable = [
    "PARTNAME",
    "LOTID",
    "QTY",
    "PACKAGE",
    "LC",
    "ADDED_BY"
  ];
  public $timestamps = false;

  public function addedBy()
  {
    return $this->belongsTo(Employee::class, 'ADDED_BY', 'EMPLOYID');
  }
}
