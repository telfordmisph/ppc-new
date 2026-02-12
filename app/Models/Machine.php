<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Machine extends Model
{
  protected $table = 'machines';

  public $timestamps = false;

  protected $fillable = [
    'name',
    'modified_by'
  ];

  public function capacityProfiles()
  {
    return $this->hasMany(BodySizeCapacityProfile::class, 'machine_id')
      ->whereNull('effective_to'); // only current profiles
  }
}
