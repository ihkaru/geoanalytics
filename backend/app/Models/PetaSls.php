<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PetaSls extends Model
{
    use HasFactory;

    protected $table = 'peta_sls';
    protected $primaryKey = 'idsubsls';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'idsubsls',
        'geom',
    ];
}
