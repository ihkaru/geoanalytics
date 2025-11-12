<?php

namespace App\Console\Commands;

use App\Models\Usaha;
use Illuminate\Console\Command;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class GeocodeUsahasCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'geocode:usahas';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Geocode latitude and longitude for Usaha records with missing coordinates but existing addresses, using the Nominatim API.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting geocoding process for Usaha records...');

        $usahasToGeocode = DB::table('usahas')
            ->leftJoin('muatan_subsls', function ($join) {
                // Use TRIM to handle potential whitespace/padding issues from Varchar(3) vs Varchar(255)
                $join->on(DB::raw('TRIM(usahas.kdkec)'), '=', DB::raw('TRIM(muatan_subsls.kdkec)'))
                     ->on(DB::raw('TRIM(usahas.kddesa)'), '=', DB::raw('TRIM(muatan_subsls.kddesa)'));
            })
            ->whereNull('usahas.latitude')
            ->whereNotNull('usahas.alamat')
            ->where('usahas.alamat', '!=', '')
            ->select(
                'usahas.idsbr',
                'usahas.alamat',
                DB::raw('MAX(muatan_subsls.nmdesa) as nmdesa'),
                DB::raw('MAX(muatan_subsls.nmkec) as nmkec'),
                DB::raw('MAX(muatan_subsls.nmkab) as nmkab'),
                DB::raw('MAX(muatan_subsls.nmprov) as nmprov')
            )
            ->groupBy('usahas.idsbr', 'usahas.alamat')
            ->get();

        $count = $usahasToGeocode->count();
        if ($count === 0) {
            $this->info('No Usaha records need geocoding. All done!');
            return 0;
        }

        $this->info("Found {$count} records to geocode. This may take a while due to API rate limiting (1 request/sec).");

        $progressBar = $this->output->createProgressBar($count);
        $progressBar->start();

        $successCount = 0;
        $failCount = 0;

        foreach ($usahasToGeocode as $usahaData) {
            // Detailed log as per user request
            $logParts = [
                'KAB' => $usahaData->nmkab ?: 'KOSONG',
                'KEC' => $usahaData->nmkec ?: 'KOSONG',
                'DESA' => $usahaData->nmdesa ?: 'KOSONG',
            ];
            $this->line("\n---");
            $this->line("Processing ID: {$usahaData->idsbr} | [KAB: {$logParts['KAB']}, KEC: {$logParts['KEC']}, DESA: {$logParts['DESA']}]");

            $geocoded = false;
            $finalAddress = '';

            // --- Attempt 1: Full & Unique Address ---
            $addressParts1 = array_unique(array_filter([
                $usahaData->alamat, $usahaData->nmdesa, $usahaData->nmkec, $usahaData->nmkab, $usahaData->nmprov, 'Indonesia'
            ]));
            $fullAddress1 = implode(', ', $addressParts1);
            $finalAddress = $fullAddress1; // Store last tried address
            
            $this->line("Attempting geocode with: '{$fullAddress1}'");
            $data = $this->tryGeocode($fullAddress1);

            if ($data) {
                $this->updateUsaha($usahaData->idsbr, $data, 1); // Level 1: Full Address
                $successCount++;
                $geocoded = true;
                $this->info("[SUCCESS] -> [{$data['lat']}, {$data['lon']}]");
            }

            // --- Attempt 2: Simpler Regional Address (without alamat) ---
            if (!$geocoded) {
                sleep(1); // Wait before next attempt
                $addressParts2 = array_unique(array_filter([
                    $usahaData->nmdesa, $usahaData->nmkec, $usahaData->nmkab, $usahaData->nmprov, 'Indonesia'
                ]));
                $fullAddress2 = implode(', ', $addressParts2);
                $finalAddress = $fullAddress2; // Store last tried address

                // Only try if the address is different from the first attempt
                if (!empty($fullAddress2) && $fullAddress2 !== $fullAddress1) {
                    $data = $this->tryGeocode($fullAddress2);
                    if ($data) {
                        $this->updateUsaha($usahaData->idsbr, $data, 2); // Level 2: Regional Fallback
                        $successCount++;
                        $geocoded = true;
                        $this->info("\n[SUCCESS] (Fallback) Geocoded '{$fullAddress2}' (ID: {$usahaData->idsbr}) -> [{$data['lat']}, {$data['lon']}]");
                    }
                }
            }

            // --- Final Failure Logging ---
            if (!$geocoded) {
                $failCount++;
                $this->warn("\n[FAIL] All attempts failed for Usaha ID: {$usahaData->idsbr}. Last tried address: '{$finalAddress}'");
            }
            
            // IMPORTANT: Respect Nominatim's usage policy of max 1 request per second.
            sleep(1);
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->info("\nGeocoding process finished.");
        $this->info("Successfully geocoded: {$successCount} records.");
        $this->info("Failed to geocode: {$failCount} records.");

        return 0;
    }

    /**
     * Try to geocode a given address string using Nominatim, with retries on connection errors.
     * @param string $address
     * @return array|null
     */
    private function tryGeocode(string $address): ?array
    {
        if (empty($address)) {
            return null;
        }

        $attempts = 3;
        for ($i = 1; $i <= $attempts; $i++) {
            try {
                $response = Http::withHeaders([
                    'User-Agent' => 'GeoAnalyticsApp/1.0 (https://example.com; admin@example.com)'
                ])->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $address,
                    'format' => 'json',
                    'limit' => 1,
                    'countrycodes' => 'id'
                ]);

                if ($response->successful() && count($response->json()) > 0) {
                    return $response->json()[0];
                }
                
                // If response was not successful but not an exception (e.g., 404), no need to retry.
                return null;

            } catch (ConnectionException $e) {
                $this->warn("\n[NETWORK ERROR] Attempt {$i}/{$attempts} failed for address '{$address}'. Retrying in 10 seconds...");
                if ($i === $attempts) {
                    $this->error("\n[FATAL] All network attempts failed for address '{$address}'.");
                    return null; // Give up after final attempt
                }
                sleep(10); // Wait longer after a network error
            }
        }

        return null;
    }

    /**
     * Update the Usaha model with new coordinates.
     * @param string $idsbr
     * @param array $data
     * @param int $geocodeLevel
     */
    private function updateUsaha(string $idsbr, array $data, int $geocodeLevel): void
    {
        $usahaModel = Usaha::find($idsbr);
        if ($usahaModel) {
            $usahaModel->latitude = $data['lat'];
            $usahaModel->longitude = $data['lon'];
            $usahaModel->geocode_status = $geocodeLevel;
            $usahaModel->save();
        }
    }
}
