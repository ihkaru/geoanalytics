<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MuatanSubsls extends Model
{
    protected $table = 'muatan_subsls';
    protected $primaryKey = 'idsubsls';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'idsubsls',
        'semester',
        'idsls',
        'nmsls',
        'nama_ketua',
        'jenis',
        'kdprov',
        'kdkab',
        'kdkec',
        'kddesa',
        'kdsls',
        'kdsubsls',
        'klas',
        'nmprov',
        'nmkab',
        'nmkec',
        'nmdesa',
        'kk',
        'btt',
        'bttk',
        'bku',
        'bbtt_nonusaha',
        'usaha',
        'muatan',
        'dominan',
        'berubah_batas',
        'id',
        'contact_person',
        'jam_operasional',
        'nm_ekonomi',
        'shift',
    ];
}
