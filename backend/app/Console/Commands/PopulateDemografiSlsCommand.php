<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

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

            $this->info('Aggregating data from regsosek and inserting into demografi_sls...');

            // This query joins regsosek with muatan_subsls to get the correct idsubsls,
            // then groups by it to count population (jumlah_penduduk) and households (jumlah_kk).
            // Note: This relies on the join keys between regsosek and muatan_subsls being correct.
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
                    muatan_subsls AS m ON CAST(r.kode_prov AS VARCHAR) = CAST(m.kdprov AS VARCHAR)
                                        AND CAST(r.kode_kab AS VARCHAR) = CAST(m.kdkab AS VARCHAR)
                                        AND CAST(r.kode_kec AS VARCHAR) = CAST(m.kdkec AS VARCHAR)
                                        AND CAST(r.kode_desa AS VARCHAR) = CAST(m.kddesa AS VARCHAR)
                                        AND CAST(r.kode_sls AS VARCHAR) = CAST(m.kdsls AS VARCHAR)
                                        AND CAST(r.kode_subsls AS VARCHAR) = CAST(m.kdsubsls AS VARCHAR)
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
                return 1;
            }

        } else {
            $this->info('Operation cancelled.');
        }

        return 0;
    }
}
