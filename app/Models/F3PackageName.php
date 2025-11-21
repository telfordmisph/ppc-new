<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class F3PackageName extends Model
{
  use HasFactory;

  protected $table = 'f3_package_names';
  protected $fillable = ['package_name'];
  public $timestamps = false;
}
