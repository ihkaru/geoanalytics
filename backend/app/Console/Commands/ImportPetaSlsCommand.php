<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class ImportPetaSlsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:peta-sls {--chunk-size=100} {--force}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import GeoJSON features from data/Final_SLS_202416104.geojson into the peta_sls table.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $filePath = base_path('data_geojson/Final_SLS_202416104.geojson');

        if (!File::exists($filePath)) {
            $this->error("File not found at: {$filePath}");
            return 1;
        }

        if ($this->option('force') || $this->confirm('Do you want to truncate the peta_sls table before importing? This will delete all existing data in it.')) {
            DB::table('peta_sls')->truncate();
            $this->info('peta_sls table truncated.');
        }

        $this->info("Reading GeoJSON file... (This may take a moment for large files)");
        $geoJsonContent = File::get($filePath);
        $data = json_decode($geoJsonContent);
        unset($geoJsonContent); // Free up memory

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Failed to decode JSON: ' . json_last_error_msg());
            return 1;
        }

        if (!isset($data->features)) {
            $this->error('Invalid GeoJSON format: "features" array not found.');
            return 1;
        }

        $features = $data->features;
        $totalFeatures = count($features);
        $chunkSize = (int) $this->option('chunk-size');

        $this->info("Found {$totalFeatures} features to import. Starting import in chunks of {$chunkSize}...");

        $bar = $this->output->createProgressBar($totalFeatures);
        $bar->start();

        $featureChunks = array_chunk($features, $chunkSize);
        unset($features); // Free up memory

        foreach ($featureChunks as $chunk) {
            DB::transaction(function () use ($chunk, $bar) {
                foreach ($chunk as $feature) {
                    if (!isset($feature->properties->idsubsls)) {
                        $this->warn('Skipping feature without idsubsls property.');
                        continue;
                    }

                    $idsubsls = $feature->properties->idsubsls;
                    $geometryJson = json_encode($feature->geometry);

                    // Use updateOrInsert to be safe if not truncating
                    DB::table('peta_sls')->updateOrInsert(
                        ['idsubsls' => $idsubsls],
                        ['geom' => DB::raw("ST_SetSRID(ST_GeomFromGeoJSON('{$geometryJson}'), 4326)")]
                    );
                    $bar->advance();
                }
            });
        }

        $bar->finish();
        $this->info("\nSuccessfully imported {$totalFeatures} features into the peta_sls table.");

        return 0;
    }
}
