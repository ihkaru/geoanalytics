<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReferensiKbli extends Model
{
    use HasFactory;

    protected $table = 'referensi_kbli';
    
    // This model has a composite primary key, which Eloquent doesn't support out of the box.
    // We'll treat it as a model without a primary key for basic operations.
    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'kbli_id',
        'tahun',
        'level',
        'judul',
        'deskripsi',
    ];
}
