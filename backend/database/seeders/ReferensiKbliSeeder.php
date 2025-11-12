<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReferensiKbliSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting KBLI 2020 Seeder...');

        // The 'data' directory is mounted as 'data_geojson' inside the container
        $filePath = base_path('data_geojson/kbli_data.csv');

        if (!file_exists($filePath)) {
            $this->command->error('kbli_data.csv not found in the mounted data directory.');
            return;
        }

        if ($this->command->confirm('Do you want to truncate the referensi_kbli table before seeding?', true)) {
            DB::table('referensi_kbli')->truncate();
            $this->command->info('referensi_kbli table truncated.');
        }

        // Get total line count for progress bar
        $lineCount = 0;
        $handle = fopen($filePath, 'r');
        while(!feof($handle)){
          fgets($handle);
          $lineCount++;
        }
        fclose($handle);

        $this->command->info('Seeding data from kbli_data.csv...');
        $progressBar = $this->command->getOutput()->createProgressBar($lineCount - 1);
        $progressBar->start();

        $chunkSize = 500;
        $chunk = [];
        
        if (($handle = fopen($filePath, 'r')) !== FALSE) {
            $header = fgetcsv($handle); // Read header: kbli_id,level,judul,deskripsi
            $headerMap = array_flip($header);

            while (($row = fgetcsv($handle)) !== FALSE) {
                $dataRow = [
                    'kbli_id' => $row[$headerMap['kbli_id']],
                    'tahun' => 2020, // Hardcode the version year
                    'level' => $row[$headerMap['level']],
                    'judul' => $row[$headerMap['judul']],
                    'deskripsi' => $row[$headerMap['deskripsi']],
                ];
                $chunk[] = $dataRow;

                if (count($chunk) >= $chunkSize) {
                    DB::table('referensi_kbli')->insertOrIgnore($chunk);
                    $chunk = [];
                }
                $progressBar->advance();
            }

            if (!empty($chunk)) {
                DB::table('referensi_kbli')->insertOrIgnore($chunk);
            }

            fclose($handle);
        }

        $progressBar->finish();
        $this->command->info("\nKBLI 2020 data seeded successfully.");
    }
}
