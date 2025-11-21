<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class F1F2Out extends Model
{
  protected $table = 'customer_data_wip_out';
  public $timestamps = false;

  protected $fillable = [
    'part_name',
    'lot_id',
    'out_date',
    'qty',
    'residual',
    'test_part',
    'test_lot_id',
    'focus_group',
    'package',
    'process_site',
    'test_site',
    'tray',
    'bulk',
    'date_loaded',
    'process_group',
    'ramp_time',
  ];
}
