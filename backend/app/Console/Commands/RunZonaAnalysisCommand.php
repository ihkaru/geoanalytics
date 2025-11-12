<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RunZonaAnalysisCommand extends Command
{
    protected $signature = 'analisis:run-zona 
                            {--kbli=* : Jalankan analisis hanya untuk KBLI 5 digit tertentu} 
                            {--radius=* : Jalankan analisis hanya untuk radius tertentu (dalam meter)}
                            {--truncate=true : Hapus data lama di tabel cache sebelum memulai}';

    protected $description = 'Menjalankan analisis zona peluang ekonomi dan menyimpan hasilnya ke tabel cache.';

    public function handle()
    {
        $this->info('Memulai proses analisis zona peluang ekonomi...');
        Log::info('Starting Zona Analysis...');

        // --- Konfigurasi Analisis ---
        // Jika tidak ada KBLI spesifik yang diberikan, gunakan daftar prioritas ini.
        $kblisToProcess = $this->option('kbli') ?: ['47721', '56303', '45407', '47112', '56102', '56103'];
        // Jika tidak ada radius spesifik, gunakan daftar ini.
        $radiiToProcess = $this->option('radius') ?: [500, 1000, 1500];

        if ($this->option('truncate')) {
            $this->info('Menghapus data lama dari analisis_zona_cache...');
            DB::table('analisis_zona_cache')->truncate();
        }

        // Loop untuk setiap KBLI dan Radius
        foreach ($kblisToProcess as $kbli) {
            foreach ($radiiToProcess as $radius) {
                $this->line("--- Menganalisis KBLI: {$kbli}, Radius: {$radius} meter ---");
                Log::info("Processing KBLI: {$kbli}, Radius: {$radius}m");

                try {
                    DB::statement($this->buildAnalysisQuery(), [
                        'kbli' => $kbli,
                        'radius' => $radius,
                    ]);
                    $this->info("Analisis untuk KBLI {$kbli} @ {$radius}m selesai.");
                } catch (\Exception $e) {
                    $this->error("Gagal menganalisis KBLI {$kbli} @ {$radius}m: " . $e->getMessage());
                    Log::error("Failed analysis for KBLI {$kbli} @ {$radius}m: " . $e->getMessage());
                }
            }
        }

        $this->info('Proses analisis zona peluang ekonomi selesai.');
        Log::info('Zona Analysis Finished.');
        return 0;
    }

    private function buildAnalysisQuery(): string
    {
        /**
         * Kueri ini melakukan analisis dalam beberapa langkah menggunakan Common Table Expressions (CTE):
         * 1.  `competing_usahas`: Mengambil semua usaha yang cocok dengan KBLI yang sedang dianalisis.
         * 2.  `service_areas`: Membuat buffer (lingkaran jangkauan) di sekitar setiap usaha kompetitor.
         * 3.  `total_served_area`: Menggabungkan semua buffer menjadi satu poligon besar area yang terlayani.
         * 4.  `sls_analysis`: Untuk setiap SLS, hitung:
         *     - `unserved_geom`: Geometri area SLS yang TIDAK terlayani.
         *     - `unserved_area_ratio`: Persentase area SLS yang tidak terlayani.
         *     - `skor_potensi`: Jumlah penduduk di area yang tidak terlayani.
         *     - `jumlah_usaha_sejenis`: Jumlah kompetitor yang jangkauannya menyentuh SLS ini.
         * 5.  Final `INSERT`: Memasukkan hasil perhitungan ke tabel `analisis_zona_cache` dengan klasifikasi zona.
         */
        return "
            INSERT INTO analisis_zona_cache (
                idsubsls, kbli_5_digit, radius_meter, 
                skor_potensi, zona, jumlah_penduduk_total, 
                jumlah_usaha_sejenis, rasio_penduduk_per_usaha, 
                created_at, updated_at
            )
            WITH 
            competing_usahas AS (
                SELECT geom FROM usahas WHERE kbli = :kbli AND geom IS NOT NULL
            ),
            service_areas AS (
                SELECT ST_Buffer(geom::geography, :radius)::geometry as geom FROM competing_usahas
            ),
            total_served_area AS (
                SELECT ST_Union(geom) as geom FROM service_areas
            ),
            sls_analysis AS (
                SELECT 
                    p.idsubsls,
                    d.jumlah_penduduk,
                    COALESCE(ST_Area(p.geom::geography), 0) as sls_area,
                    
                    -- Hitung geometri area yang tidak terlayani
                    ST_Difference(p.geom, tsa.geom) as unserved_geom,
                    
                    -- Hitung jumlah usaha kompetitor yang jangkauannya menyentuh SLS ini
                    (SELECT COUNT(*) FROM service_areas sa WHERE ST_Intersects(p.geom, sa.geom)) as jumlah_usaha_sejenis
                FROM 
                    peta_sls p
                JOIN 
                    demografi_sls d ON p.idsubsls = d.idsubsls
                CROSS JOIN 
                    total_served_area tsa
                WHERE 
                    p.geom IS NOT NULL AND ST_IsValid(p.geom)
            )
            SELECT
                idsubsls,
                :kbli as kbli_5_digit,
                :radius as radius_meter,
                
                -- Skor Potensi: Estimasi jumlah penduduk di area yang tidak terlayani
                (jumlah_penduduk * (ST_Area(unserved_geom::geography) / sls_area)) as skor_potensi,
                
                -- Klasifikasi Zona berdasarkan skor potensi
                CASE 
                    WHEN (jumlah_penduduk * (ST_Area(unserved_geom::geography) / sls_area)) > 250 THEN 'Merah'
                    WHEN (jumlah_penduduk * (ST_Area(unserved_geom::geography) / sls_area)) > 50 THEN 'Kuning'
                    ELSE 'Jenuh'
                END as zona,
                
                jumlah_penduduk as jumlah_penduduk_total,
                jumlah_usaha_sejenis,
                
                -- Rasio: Jumlah penduduk dibagi jumlah usaha (+1 untuk menghindari div by zero)
                CASE 
                    WHEN jumlah_usaha_sejenis = 0 THEN jumlah_penduduk
                    ELSE jumlah_penduduk / (jumlah_usaha_sejenis + 1)
                END as rasio_penduduk_per_usaha,
                
                NOW(),
                NOW()
            FROM 
                sls_analysis
            WHERE 
                sls_area > 0; -- Hindari pembagian dengan nol jika ada SLS dengan area 0
        ";
    }
}