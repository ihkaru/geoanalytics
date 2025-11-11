<?php

namespace App\Console\Commands;

use App\Models\Usaha;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class FixUsahaDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:fix-usaha';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Orchestrates the cleaning and fixing of the Usaha dataset.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Usaha data fixing process...');

        // Step 1: Fix invalid KBLI codes (empty strings)
        $this->line("\nStep 1: Fixing invalid KBLI codes (empty strings)...");
        $updatedKbliCount = Usaha::where('kbli', '')->update(['kbli' => null]);
        if ($updatedKbliCount > 0) {
            $this->info("Success: Updated {$updatedKbliCount} records with empty KBLI to NULL.");
        } else {
            $this->info("No records with empty KBLI found to update.");
        }

        // Step 2: Call the geocoding command for records with missing coordinates
        $this->line("\nStep 2: Starting geocoding for records with missing coordinates...");
        $this->info("This will call the 'geocode:usahas' command. The process may take a long time due to API rate limiting.");
        
        // Calling the command and waiting for it to finish.
        // The output of the called command will be streamed to the console.
        Artisan::call('geocode:usahas', [], $this->getOutput());

        $this->info("\nUsaha data fixing process finished.");
        return 0;
    }
}
