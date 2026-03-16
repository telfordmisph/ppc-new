<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PpcPackagePlRule extends Model
{
    protected $table = 'ppc_package_pl_rules';

    protected $fillable = [
        'package',
        'production_line',
        'factory',
        'lead_count',
        'partname_like',
        'priority',
        'is_active',
        'valid_from',
        'valid_to',
        'note',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'lead_count' => 'integer',
        'priority'   => 'integer',
        'valid_from' => 'date',
        'valid_to'   => 'date',
    ];
}
