<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DemografiSls extends Model
{
    use HasFactory;

    protected $table = 'demografi_sls';
    protected $primaryKey = 'idsubsls';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'idsubsls',
        'kode_desa',
        'nama_desa',
        'tahun',
        'jumlah_penduduk',
        'jumlah_kk',
    ];
}
