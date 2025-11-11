<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RegsosekSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting Regsosek Seeder...');

        $filePath = database_path('data/regsosek.csv');
        if (!file_exists($filePath)) {
            $this->command->error('regsosek.csv not found in database/data directory.');
            return;
        }

        // Ask for confirmation
        if ($this->command->confirm('Do you want to truncate the regsosek table before seeding?', true)) {
            DB::table('regsosek')->truncate();
            $this->command->info('Regsosek table truncated.');
        }

        // Get total line count for progress bar without loading file into memory
        $lineCount = 0;
        $handle = fopen($filePath, 'r');
        while(!feof($handle)){
          fgets($handle);
          $lineCount++;
        }
        fclose($handle);

        $this->command->info('Seeding data from regsosek.csv. This might take a while...');
        $progressBar = $this->command->getOutput()->createProgressBar($lineCount - 1); // -1 for header
        $progressBar->start();

        $chunkSize = 500;
        $chunk = [];
        $header = [];
        
        if (($handle = fopen($filePath, 'r')) !== FALSE) {
            $header = fgetcsv($handle); // Read header
            $headerMap = array_flip($header);

            while (($row = fgetcsv($handle)) !== FALSE) {

                // Validate the primary key component 'r401' before processing the row
                $r401_value = $row[$headerMap['r401']] ?? null;
                if ($r401_value === '\N' || $r401_value === '' || $r401_value === null) {
                    // $this->command->warn('Skipping row with empty r401.'); // Optional: can be noisy
                    continue; // Skip this row entirely
                }
                
                $tgl_lahir = null;
                try {
                    $thn = $row[$headerMap['r406_thn']];
                    $bln = $row[$headerMap['r406_bln']];
                    $tgl = $row[$headerMap['r406_tgl']];
                    if (checkdate((int)$bln, (int)$tgl, (int)$thn)) {
                        $tgl_lahir = Carbon::createFromDate($thn, $bln, $tgl)->toDateString();
                    }
                } catch (\Exception $e) {
                    // Ignore date creation errors
                }

                $getValue = function($key, $isNumeric = false) use ($row, $headerMap) {
                    $value = $row[$headerMap[$key]] ?? null;
                    if ($value === '\N' || $value === '') {
                        return null;
                    }
                    return $isNumeric ? (int)$value : $value;
                };

                $dataRow = [
                    'kode_prov' => $getValue('kode_prov'),
                    'kode_kab' => $getValue('kode_kab'),
                    'kode_kec' => $getValue('kode_kec'),
                    'kode_desa' => $getValue('kode_desa'),
                    'kode_sls' => $getValue('kode_sls'),
                    'kode_subsls' => $getValue('kode_subsls'),
                    'id_rt' => $getValue('id_rt'),
                    'alamat' => $getValue('alamat'),
                    'nama_kk' => $getValue('r108'),
                    'r112' => $getValue('r112', true),
                    'r301a' => $getValue('r301a', true),
                    'r302' => $getValue('r302', true),
                    'r306a' => $getValue('r306a', true),
                    'r307a' => $getValue('r307a', true),
                    'r308' => $getValue('r308', true),
                    'r401' => $getValue('r401', true),
                    'nama_art' => $getValue('r402'),
                    'nik' => $getValue('r403'),
                    'r405' => $getValue('r405', true),
                    'r406_tanggal_lahir' => $tgl_lahir,
                    'r407' => $getValue('r407', true),
                    'r408' => $getValue('r408', true),
                    'r413' => $getValue('r413', true),
                    'r415' => $getValue('r415', true),
                    'r416a' => $getValue('r416a', true),
                    'r417' => $getValue('r417', true),
                    'r420a' => $getValue('r420a', true),
                    'r430' => $getValue('r430'),
                    'r431a' => $getValue('r431a'),
                    'r501a_k1' => $getValue('r501a_k1', true),
                    'r501b_k1' => $getValue('r501b_k1', true),
                    'r502h' => $getValue('r502h', true),
                    'r506' => $getValue('r506', true),
                ];
                $chunk[] = $dataRow;

                if (count($chunk) >= $chunkSize) {
                    DB::table('regsosek')->insertOrIgnore($chunk);
                    $chunk = [];
                }
                $progressBar->advance();
            }

            // Insert any remaining records
            if (!empty($chunk)) {
                DB::table('regsosek')->insertOrIgnore($chunk);
            }

            fclose($handle);
        }

        $progressBar->finish();
        $this->command->info("\nRegsosek data seeded successfully.");
    }
}