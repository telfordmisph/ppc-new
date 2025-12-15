<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class F3Out extends Model
{
  protected $table = 'f3_out';
  public $timestamps = false;

  protected $fillable = [
    'date_loaded',
    'date_received',
    'packing_list_srf',
    'po_number',
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
    'cycle_time',
  ];
}
