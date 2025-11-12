<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportGeocodeCommand extends Command
{
    protected $signature = 'usaha:import-geocode {--chunk-size=500}';
    protected $description = 'Imports geocoded results from the backup CSV file into the usahas table.';

    public function handle()
    {
        $this->info('Starting geocode import process...');

        $backupPath = database_path('backups/geocode_backup.csv');

        if (!File::exists($backupPath)) {
            $this->error("Backup file not found at: {$backupPath}");
            $this->error("Please run 'php artisan usaha:export-geocode' first.");
            return 1;
        }

        $this->info("Reading backup file: {$backupPath}");

        $header = null;
        $rowCount = 0;
        $chunkSize = (int) $this->option('chunk-size');
        $totalUpdates = 0;

        $handle = fopen($backupPath, 'r');
        $header = fgetcsv($handle); // Read header
        $headerMap = array_flip($header);

        $progressBar = $this->output->createProgressBar();
        $progressBar->start();

        DB::transaction(function () use ($handle, $headerMap, $chunkSize, &$totalUpdates, $progressBar) {
            $chunk = [];
            while (($row = fgetcsv($handle)) !== FALSE) {
                $chunk[] = [
                    'idsbr' => $row[$headerMap['idsbr']],
                    'latitude' => $row[$headerMap['latitude']],
                    'longitude' => $row[$headerMap['longitude']],
                    'geocode_status' => $row[$headerMap['geocode_status']],
                    'geom_wkt' => $row[$headerMap['geom_wkt']],
                ];

                if (count($chunk) >= $chunkSize) {
                    $totalUpdates += $this->updateChunk($chunk);
                    $chunk = [];
                }
                $progressBar->advance();
            }

            if (!empty($chunk)) {
                $totalUpdates += $this->updateChunk($chunk);
            }
        });

        fclose($handle);
        $progressBar->finish();

        $this->info("\nSuccessfully imported geocode data. Total records updated: {$totalUpdates}");
        return 0;
    }

    private function updateChunk(array $chunk): int
    {
        $caseStatements = [];
        $ids = [];

        foreach (['latitude', 'longitude', 'geocode_status', 'geom'] as $column) {
            $caseStatements[$column] = "CASE idsbr ";
        }

        foreach ($chunk as $row) {
            $ids[] = "'" . $row['idsbr'] . "'";
            $caseStatements['latitude'] .= "WHEN '" . $row['idsbr'] . "' THEN " . $row['latitude'] . " ";
            $caseStatements['longitude'] .= "WHEN '" . $row['idsbr'] . "' THEN " . $row['longitude'] . " ";
            $caseStatements['geocode_status'] .= "WHEN '" . $row['idsbr'] . "' THEN '" . $row['geocode_status'] . "' ";
            $caseStatements['geom'] .= "WHEN '" . $row['idsbr'] . "' THEN ST_GeomFromText('" . $row['geom_wkt'] . "', 4326) ";
        }

        $ids = implode(',', $ids);

        $updateQuery = "
            UPDATE usahas SET
                latitude = {$caseStatements['latitude']} END,
                longitude = {$caseStatements['longitude']} END,
                geocode_status = {$caseStatements['geocode_status']} END,
                geom = {$caseStatements['geom']} END
            WHERE idsbr IN ({$ids})
        ";

        return DB::update($updateQuery);
    }
}