<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\F3RawPackage;

class F3 extends Model
{
  protected $table = 'f3';
  public $timestamps = false;

  protected $fillable = [
    'date_received',
    'packing_list_srf',
    'po_number',
    'running_ct',
    'machine_number',
    'part_number',
    'package_code',
    'package',
    'lot_number',
    'process_req',
    'qty',
    'good',
    'rej',
    'res',
    'date_commit',
    'actual_date_time',
    'status',
    'do_number',
    'remarks',
    'doable',
    'gap_analysis',
    'modified_by',
    'cycle_time',
  ];

  public function package()
  {
    return $this->belongsTo(F3RawPackage::class, 'package');
  }
}
