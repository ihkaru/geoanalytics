<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UsahaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('usahas')->truncate(); // Clear the table before seeding

        $csvPath = base_path('data_geojson/base_usaha.csv');
        $file = fopen($csvPath, 'r');

        $header = fgetcsv($file);
        $chunk = [];
        $chunkSize = 500;

        while (($row = fgetcsv($file)) !== false) {
            $rowData = array_combine($header, $row);

            $chunk[] = [
                'idsbr' => $rowData['idsbr'],
                'nama_usaha' => $rowData['nama_usaha'],
                'alamat' => $rowData['alamat'],
                'kdprov' => $rowData['kdprov'],
                'kdkab' => $rowData['kdkab'],
                'kdkec' => $rowData['kdkec'],
                'kddesa' => $rowData['kddesa'],
                'status_usaha' => is_numeric($rowData['status_usaha']) ? (int)$rowData['status_usaha'] : null,
                'skala_usaha' => $rowData['skala_usaha'],
                'nama_komersial_usaha' => $rowData['nama_komersial_usaha'],
                'latitude' => $this->safeParseCoordinate($rowData['latitude'], 90.0),
                'longitude' => $this->safeParseCoordinate($rowData['longitude'], 180.0),
                'nomor_whatsapp' => $rowData['nomor_whatsapp'],
                'idsubsls' => $rowData['idsubsls'],
                'kegiatan_usaha' => $rowData['kegiatan_usaha'] === '-' ? null : $rowData['kegiatan_usaha'],
                'kategori_usaha' => $rowData['kategori_usaha'],
                'kbli' => $rowData['kbli'],
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (count($chunk) >= $chunkSize) {
                DB::table('usahas')->insert($chunk);
                $chunk = [];
            }
        }

        // Insert any remaining records
        if (!empty($chunk)) {
            DB::table('usahas')->insert($chunk);
        }

        fclose($file);
    }

    /**
     * Safely parses a coordinate string, handling comma decimals and range validation.
     *
     * @param string $value The coordinate string from CSV.
     * @param float $maxAbsValue The maximum absolute value for the coordinate (e.g., 90 for latitude, 180 for longitude).
     * @return float|null
     */
    private function safeParseCoordinate(string $value, float $maxAbsValue): ?float
    {
        // Replace comma decimal separator with period
        $cleanedValue = str_replace(',', '.', $value);

        // If after cleaning, it's not numeric or is empty, return null
        if (!is_numeric($cleanedValue) || $cleanedValue === '') {
            return null;
        }

        $floatValue = (float)$cleanedValue;

        // If the value is outside the expected range, treat as invalid
        if (abs($floatValue) > $maxAbsValue) {
            return null;
        }

        return $floatValue;
    }
}
