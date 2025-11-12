<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ExportGeocodeCommand extends Command
{
    protected $signature = 'usaha:export-geocode';
    protected $description = 'Exports geocoded results from the usahas table to a backup CSV file, updating existing entries.';

    public function handle()
    {
        $this->info('Starting geocode export process...');

        $backupDir = database_path('backups');
        $backupPath = $backupDir . '/geocode_backup.csv';

        // Ensure the backup directory exists
        if (!File::isDirectory($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        // 1. Load existing backup into an associative array for quick lookups
        $existingData = [];
        if (File::exists($backupPath)) {
            $this->info("Reading existing backup file: {$backupPath}");
            $handle = fopen($backupPath, 'r');
            $header = fgetcsv($handle); // Skip header
            while (($row = fgetcsv($handle)) !== FALSE) {
                $rowData = array_combine($header, $row);
                $existingData[$rowData['idsbr']] = $rowData;
            }
            fclose($handle);
        }

        // 2. Query new/updated data from the database
        $this->info('Querying database for latest geocoded results...');
        $geocodedUsahas = DB::table('usahas')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->select('idsbr', 'latitude', 'longitude', 'geocode_status', DB::raw('ST_AsText(geom) as geom_wkt'))
            ->get();

        if ($geocodedUsahas->isEmpty()) {
            $this->info('No geocoded data found in the database. No changes made to backup file.');
            return 0;
        }

        // 3. Merge database results into the existing data
        $this->info("Found {$geocodedUsahas->count()} geocoded records in database. Merging results...");
        $updateCount = 0;
        foreach ($geocodedUsahas as $usaha) {
            if (isset($existingData[$usaha->idsbr]) && $existingData[$usaha->idsbr]['latitude'] == $usaha->latitude) {
                // Data is the same, no need to mark as updated
            } else {
                $updateCount++;
            }
            $existingData[$usaha->idsbr] = [
                'idsbr' => $usaha->idsbr,
                'latitude' => $usaha->latitude,
                'longitude' => $usaha->longitude,
                'geocode_status' => $usaha->geocode_status,
                'geom_wkt' => $usaha->geom_wkt,
            ];
        }

        // 4. Write the consolidated data back to the CSV
        $this->info("Writing consolidated data to backup file. Total updates/additions: {$updateCount}");
        $handle = fopen($backupPath, 'w');
        $header = ['idsbr', 'latitude', 'longitude', 'geocode_status', 'geom_wkt'];
        fputcsv($handle, $header);

        foreach ($existingData as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);

        $this->info("Successfully exported/updated " . count($existingData) . " records to {$backupPath}");
        return 0;
    }
}