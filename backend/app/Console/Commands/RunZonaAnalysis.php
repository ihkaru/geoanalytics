<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RunZonaAnalysis extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'analisis:run-zona';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Runs the economic opportunity zone analysis and populates the analisis_zona_cache table.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting economic opportunity zone analysis (with detailed logging)...');

        // Find top 3 KBLIs from the usahas table
        $this->info('Finding most common KBLIs from the usahas table...');
        $priorityKBLIs = DB::table('usahas')
            ->select('kbli')
            ->whereNotNull('kbli')
            ->whereRaw('LENGTH(kbli) = 5')
            ->groupBy('kbli')
            ->orderByRaw('COUNT(kbli) DESC')
            ->limit(3)
            ->pluck('kbli');

        if ($priorityKBLIs->isEmpty()) {
            $this->error('No 5-digit KBLI codes found in the usahas table. Cannot run analysis.');
            return 1;
        }

        $this->info('Will run analysis for the following top KBLIs: ' . $priorityKBLIs->implode(', '));
        $radii = [500, 1000, 1500];

        if (!$this->confirm('This will truncate the analisis_zona_cache table. Continue?', true)) {
            $this->info('Analysis cancelled.');
            return 0;
        }
        
        DB::table('analisis_zona_cache')->truncate();
        $this->info('analisis_zona_cache table truncated.');

        $totalCombinations = count($priorityKBLIs) * count($radii);
        $progressBar = $this->output->createProgressBar($totalCombinations);
        $progressBar->start();

        foreach ($priorityKBLIs as $kbli) {
            foreach ($radii as $radius) {
                $this->info("\n\n--- Analyzing KBLI: {$kbli}, Radius: {$radius}m ---");

                try {
                    // Log 1: Count businesses for this KBLI
                    $usahaCount = DB::table('usahas')->where('kbli', $kbli)->whereNotNull('geom')->count();
                    $this->line("[LOG 1/5] Found {$usahaCount} businesses with geometry for KBLI {$kbli}.");

                    if ($usahaCount === 0) {
                        $this->warn("Skipping: No businesses found for this KBLI.");
                        $progressBar->advance();
                        continue;
                    }

                    // Log 2: Check if supply area can be generated
                    $supplyAreaGeom = DB::selectOne("SELECT ST_IsValid(ST_Union(ST_Buffer(geom::geography, ?)::geometry)) as is_valid FROM usahas WHERE kbli = ? AND geom IS NOT NULL", [$radius, $kbli]);
                    $this->line("[LOG 2/5] Supply area geometry validity check: " . ($supplyAreaGeom->is_valid ? 'Valid' : 'Invalid'));

                    // Log 3: Count base SLS with demand
                    $slsDemandCount = DB::table('peta_sls as ps')->join('demografi_sls as ds', 'ps.idsubsls', '=', 'ds.idsubsls')->where('ds.tahun', 2022)->count();
                    $this->line("[LOG 3/5] Found {$slsDemandCount} total SLS areas with demographic data.");
                    if ($slsDemandCount === 0) {
                        $this->error("Aborting: No SLS with demand data found.");
                        return 1;
                    }

                    // Log 4: Run the actual analysis query
                    $this->line("[LOG 4/5] Executing main analysis and insert query...");
                    $query = "
                        INSERT INTO analisis_zona_cache (idsubsls, kbli_5_digit, radius_meter, skor_potensi, zona, created_at, updated_at)
                        WITH supply_areas AS (
                            SELECT ST_Union(ST_Buffer(geom::geography, ?)::geometry) AS buffered_geom
                            FROM usahas
                            WHERE kbli = ? AND geom IS NOT NULL
                        ),
                        sls_with_demand AS (
                            SELECT ps.idsubsls, ps.geom AS sls_geom, ds.jumlah_penduduk
                            FROM peta_sls ps JOIN demografi_sls ds ON ps.idsubsls = ds.idsubsls
                            WHERE ds.tahun = 2022
                        ),
                        sls_coverage AS (
                            SELECT
                                sd.idsubsls, sd.jumlah_penduduk, sd.sls_geom,
                                COALESCE(ST_Area(ST_Intersection(sd.sls_geom, sa.buffered_geom)::geography), 0) AS covered_area,
                                ST_Area(sd.sls_geom::geography) AS total_sls_area
                            FROM sls_with_demand sd, supply_areas sa
                        )
                        SELECT
                            sc.idsubsls, ? AS kbli_5_digit, ? AS radius_meter,
                            CASE
                                WHEN sc.total_sls_area = 0 THEN sc.jumlah_penduduk
                                ELSE (1 - (sc.covered_area / sc.total_sls_area)) * sc.jumlah_penduduk
                            END AS skor_potensi,
                            CASE
                                WHEN sc.total_sls_area = 0 THEN 'Tidak Terdefinisi'
                                WHEN (1 - (sc.covered_area / sc.total_sls_area)) > 0.75 THEN 'Merah'
                                WHEN (1 - (sc.covered_area / sc.total_sls_area)) > 0.25 THEN 'Kuning'
                                ELSE 'Jenuh'
                            END AS zona,
                            NOW(), NOW()
                        FROM sls_coverage sc;
                    ";
                    
                    DB::statement($query, [$radius, $kbli, $kbli, $radius]);
                    
                    // Log 5: Verify insertion
                    $rowsInserted = DB::table('analisis_zona_cache')->where('kbli_5_digit', $kbli)->where('radius_meter', $radius)->count();
                    $this->line("[LOG 5/5] Verified: Found {$rowsInserted} rows in cache for this combination.");

                } catch (\Exception $e) {
                    $this->error("An error occurred during analysis for KBLI {$kbli}, Radius {$radius}m:");
                    $this->error($e->getMessage());
                }

                $progressBar->advance();
            }
        }

        $progressBar->finish();
        $this->info("\n\nAnalysis finished.");
        return 0;
    }
}
