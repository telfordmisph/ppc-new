<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BodySize extends Model
{
  protected $table = 'body_sizes';
  public $timestamps = false;
  protected $fillable = [
    'name',
    'modified_by'
  ];
}
