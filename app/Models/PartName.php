<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PartName extends Model
{
    use HasFactory;

    protected $table = 'ppc_partnamedb';

    protected $primaryKey = 'ppc_partnamedb_id';

    protected $fillable = [
        'Partname',
        'Focus_grp',
        'Factory',
        'PL',
        'Packagename',
        'Packagecategory',
        'Leadcount',
        'Bodysize',
        'Package',
        // 'added_by',
        // 'date_created',
    ];

    protected $casts = [
        'date_created' => 'datetime',
    ];

    public $timestamps = false;
}
