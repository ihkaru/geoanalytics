<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('regsosek', function (Blueprint $table) {
            // Using a composite primary key for uniqueness at the individual level
            $table->primary(['kode_prov', 'kode_kab', 'kode_kec', 'kode_desa', 'kode_sls', 'kode_subsls', 'id_rt', 'r401']);

            // Blok I: Keterangan Tempat & Keluarga
            $table->string('kode_prov', 2);
            $table->string('kode_kab', 2);
            $table->string('kode_kec', 3);
            $table->string('kode_desa', 3);
            $table->string('kode_sls', 4);
            $table->string('kode_subsls', 3);
            $table->unsignedBigInteger('id_rt');
            $table->text('alamat')->nullable(); // Encrypted
            $table->string('nama_kk')->nullable(); // Encrypted
            $table->unsignedSmallInteger('r112')->nullable()->comment('Jumlah Anggota Keluarga');

            // Blok III: Keterangan Perumahan (Sample)
            $table->unsignedSmallInteger('r301a')->nullable()->comment('Status Kepemilikan Bangunan');
            $table->integer('r302')->nullable()->comment('Luas Lantai');
            $table->unsignedSmallInteger('r306a')->nullable()->comment('Sumber Air Minum Utama');
            $table->unsignedSmallInteger('r307a')->nullable()->comment('Sumber Penerangan Utama');
            $table->unsignedSmallInteger('r308')->nullable()->comment('Bahan Bakar Utama Memasak');

            // Blok IV: Keterangan Individu
            $table->unsignedSmallInteger('r401')->comment('Nomor Urut Anggota Keluarga');
            $table->text('nama_art')->nullable()->comment('Nama Anggota Keluarga (Encrypted)');
            $table->string('nik', 255)->nullable()->comment('NIK (Encrypted)');
            $table->unsignedSmallInteger('r405')->nullable()->comment('Jenis Kelamin');
            $table->date('r406_tanggal_lahir')->nullable();
            $table->unsignedSmallInteger('r407')->nullable()->comment('Umur');
            $table->unsignedSmallInteger('r408')->nullable()->comment('Status Perkawinan');
            $table->unsignedSmallInteger('r413')->nullable()->comment('Jenjang Pendidikan Tertinggi');
            $table->unsignedSmallInteger('r415')->nullable()->comment('Ijazah Tertinggi');
            $table->unsignedSmallInteger('r416a')->nullable()->comment('Status Bekerja');
            $table->unsignedSmallInteger('r417')->nullable()->comment('Lapangan Usaha Pekerjaan Utama');
            $table->unsignedSmallInteger('r420a')->nullable()->comment('Kepemilikan Usaha');
            $table->string('r430', 255)->nullable()->comment('Penyakit Kronis/Menahun');
            $table->string('r431a', 255)->nullable()->comment('Kepemilikan Jaminan Kesehatan');

            // Blok V: Aset & Program (Sample)
            $table->unsignedSmallInteger('r501a_k1')->nullable()->comment('Penerima BPNT/Sembako');
            $table->unsignedSmallInteger('r501b_k1')->nullable()->comment('Penerima PKH');
            $table->unsignedSmallInteger('r502h')->nullable()->comment('Kepemilikan Mobil');
            $table->unsignedSmallInteger('r506')->nullable()->comment('Kepemilikan Rekening/Dompet Digital');

            // Indexing for faster queries
            $table->index('kode_desa');
            $table->index('kode_sls');
            $table->index('id_rt');
            $table->index('r417', 'lapangan_usaha_idx');
            $table->index('r416a', 'status_bekerja_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('regsosek');
    }
};
