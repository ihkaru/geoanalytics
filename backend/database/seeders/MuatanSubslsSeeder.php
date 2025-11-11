<?php

namespace Database\Seeders;

use App\Models\MuatanSubsls;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\LazyCollection;

class MuatanSubslsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $csvPath = '/var/www/html/data_geojson/muatan_subsls.csv';
        $this->command->info("Starting to seed muatan_subsls from: " . $csvPath);

        // Disable mass assignment protection for this operation
        MuatanSubsls::unguard();

        // Use a LazyCollection to process the large CSV file efficiently
        $collection = LazyCollection::make(function () use ($csvPath) {
            $handle = fopen($csvPath, 'r');
            while (($line = fgetcsv($handle)) !== false) {
                yield $line;
            }
            fclose($handle);
        });

        // Get the header row to use as keys
        $header = $collection->take(1)->first();
        $rows = $collection->skip(1);

        // Define integer columns that might have empty strings
        $integerColumns = [
            'id', 'klas', 'kk', 'btt', 'bttk', 'bku', 'bbtt_nonusaha', 'usaha', 'muatan', 'dominan', 'berubah_batas'
        ];

        $progressBar = $this->command->getOutput()->createProgressBar();

        DB::transaction(function () use ($rows, $header, $integerColumns, $progressBar) {
            $chunkSize = 500; // Adjust chunk size based on memory and performance
            foreach ($rows->chunk($chunkSize) as $chunk) {
                $records = [];
                foreach ($chunk as $row) {
                    if (count($header) !== count($row)) {
                        $this->command->warn("Skipping malformed row: " . implode(',', $row));
                        continue;
                    }
                    $record = array_combine($header, $row);

                    // Convert empty strings to null for integer columns
                    foreach ($integerColumns as $col) {
                        if (isset($record[$col]) && $record[$col] === '') {
                            $record[$col] = null;
                        }
                    }
                    $records[] = $record;
                }

                // Use insert instead of create for better performance
                MuatanSubsls::insert($records);
                $progressBar->advance($chunk->count());
            }
        });

        $progressBar->finish();
        $this->command->info("\nMuatan SLS seeding completed successfully.");

        MuatanSubsls::reguard();
    }
}
