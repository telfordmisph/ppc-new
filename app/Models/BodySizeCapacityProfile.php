<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BodySizeCapacityProfile extends Model
{
  protected $table = 'body_size_capacity_profiles';
  public $timestamps = false;
  protected $fillable = [
    'body_size_id',
    'capacity',
    'machine_id',
    'factory',
    'effective_from',
    'effective_to',
  ];

  public function bodySize()
  {
    return $this->belongsTo(BodySize::class, 'body_size_id');
  }

  public function machine()
  {
    return $this->belongsTo(Machine::class, 'machine_id');
  }
}
