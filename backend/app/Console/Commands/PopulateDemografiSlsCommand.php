<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PopulateDemografiSlsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'populate:demografi-sls';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populates the demografi_sls table by aggregating data from the regsosek table for the year 2022.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to populate demografi_sls table...');

        if ($this->confirm('This will truncate the demografi_sls table. Do you want to continue?', true)) {
            
            DB::table('demografi_sls')->truncate();
            $this->info('demografi_sls table truncated.');

            // --- DIAGNOSTIC LOGGING ---
            $this->info('Running diagnostics...');
            Log::info('--- Starting demografi_sls population diagnostics ---');

            $regsosekSample = DB::table('regsosek')->select('kode_prov', 'kode_kab', 'kode_kec', 'kode_desa', 'kode_sls', 'kode_subsls')->first();
            $muatanSample = DB::table('muatan_subsls')->select('kdprov', 'kdkab', 'kdkec', 'kddesa', 'kdsls', 'kdsubsls')->first();

            Log::info('Regsosek Sample:', (array)$regsosekSample);
            $this->info('Regsosek Sample: ' . json_encode($regsosekSample));
            Log::info('Muatan SLS Sample:', (array)$muatanSample);
            $this->info('Muatan SLS Sample: ' . json_encode($muatanSample));

            $joinCheckQuery = "
                SELECT COUNT(*) as match_count
                FROM regsosek AS r
                JOIN muatan_subsls AS m ON r.kode_prov = m.kdprov
                                        AND LPAD(r.kode_kab, 2, '0') = m.kdkab
                                        AND LPAD(r.kode_kec, 3, '0') = m.kdkec
                                        AND LPAD(r.kode_desa, 3, '0') = m.kddesa
                                        AND LPAD(r.kode_sls, 4, '0') = m.kdsls
                                        AND (r.kode_subsls = m.kdsubsls OR (r.kode_subsls = '0' AND m.kdsubsls = ''))
            ";
            
            $matchCount = DB::select($joinCheckQuery)[0]->match_count;
            $this->info("Diagnostic JOIN Check: Found {$matchCount} matching rows between regsosek and muatan_subsls.");
            Log::info("Diagnostic JOIN Check: Found {$matchCount} matching rows.");
            // --- END DIAGNOSTIC ---

            if ($matchCount == 0) {
                $this->error('No matching rows found. Aborting population. Please check data consistency between regsosek and muatan_subsls tables.');
                Log::error('Aborted demografi_sls population: No matching rows found in JOIN.');
                return 1;
            }

            $this->info('Aggregating data from regsosek and inserting into demografi_sls...');

            $query = "
                INSERT INTO demografi_sls (idsubsls, jumlah_penduduk, jumlah_kk, tahun, kode_desa)
                SELECT
                    m.idsubsls,
                    COUNT(r.r401) AS jumlah_penduduk,
                    COUNT(DISTINCT r.id_rt) AS jumlah_kk,
                    2022 AS tahun,
                    m.kddesa AS kode_desa
                FROM
                    regsosek AS r
                JOIN
                    muatan_subsls AS m ON r.kode_prov = m.kdprov
                                        AND LPAD(r.kode_kab, 2, '0') = m.kdkab
                                        AND LPAD(r.kode_kec, 3, '0') = m.kdkec
                                        AND LPAD(r.kode_desa, 3, '0') = m.kddesa
                                        AND LPAD(r.kode_sls, 4, '0') = m.kdsls
                                        AND (r.kode_subsls = m.kdsubsls OR (r.kode_subsls = '0' AND m.kdsubsls = ''))
                WHERE r.r401 IS NOT NULL
                GROUP BY
                    m.idsubsls, m.kddesa
            ";

            try {
                DB::statement($query);
                $this->info('Successfully populated demografi_sls table.');
            } catch (\Exception $e) {
                $this->error('An error occurred during the database operation:');
                $this->error($e->getMessage());
                Log::error('Failed to populate demografi_sls: ' . $e->getMessage());
                return 1;
            }

        } else {
            $this->info('Operation cancelled.');
        }

        return 0;
    }
}
