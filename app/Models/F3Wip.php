<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class F3Wip extends Model
{
  protected $table = 'f3_wip';
  public $timestamps = false;

  protected $fillable = [
    'date_loaded',
    'running_ct',
    'date_received',
    'packing_list_srf',
    'po_number',
    'machine_number',
    'part_number',
    'pkg_code',
    'package',
    'lot_number',
    'process_req',
    'quantity',
    'good',
    'rej',
    'res',
    'date_commit',
    'actual_date_time',
    'status',
    'do_number',
    'remarks',
    'doable',
    'focus_group',
    'gap_analysis',
    'cycle_time',
  ];
}
