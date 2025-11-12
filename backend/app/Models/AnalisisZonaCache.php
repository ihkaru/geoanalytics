<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnalisisZonaCache extends Model
{
    use HasFactory;

    protected $table = 'analisis_zona_cache';

    // This model has a composite primary key, which Eloquent doesn't support out of the box.
    // We'll treat it as a model without a primary key for basic operations.
    protected $primaryKey = null;
    public $incrementing = false;
    
    // Timestamps are managed by the migration's $table->timestamps()
    // public $timestamps = true; // This is the default

    protected $fillable = [
        'idsubsls',
        'kbli_5_digit',
        'radius_meter',
        'skor_potensi',
        'zona',
    ];
}
