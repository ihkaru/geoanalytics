<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Usaha extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'usahas';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'idsbr';

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * The data type of the primary key.
     *
     * @var string
     */
    protected $keyType = 'string';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'idsbr',
        'nama_usaha',
        'alamat',
        'kdprov',
        'kdkab',
        'kdkec',
        'kddesa',
        'status_usaha',
        'skala_usaha',
        'nama_komersial_usaha',
        'latitude',
        'longitude',
        'geocode_status',
        'nomor_whatsapp',
        'idsubsls',
        'kegiatan_usaha',
        'kategori_usaha',
        'kbli',
    ];
}
