<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Regsosek extends Model
{
    use HasFactory;

    protected $table = 'regsosek';

    // This model has a composite primary key, which Eloquent doesn't support out of the box.
    // We'll treat it as a model without a primary key for basic operations.
    protected $primaryKey = null;
    public $incrementing = false;
    public $timestamps = false;

    protected $fillable = [
        'kode_prov',
        'kode_kab',
        'kode_kec',
        'kode_desa',
        'kode_sls',
        'kode_subsls',
        'id_rt',
        'alamat',
        'nama_kk',
        'r112',
        'r301a',
        'r302',
        'r306a',
        'r307a',
        'r308',
        'r401',
        'nama_art',
        'nik',
        'r405',
        'r406_tanggal_lahir',
        'r407',
        'r408',
        'r413',
        'r415',
        'r416a',
        'r417',
        'r420a',
        'r430',
        'r431a',
        'r501a_k1',
        'r501b_k1',
        'r502h',
        'r506',
    ];

    protected $casts = [
        'r406_tanggal_lahir' => 'date',
    ];
}
