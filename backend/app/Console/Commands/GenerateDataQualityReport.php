<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Output\ConsoleOutput;

class GenerateDataQualityReport extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'data:quality-report';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generates a data quality report in Markdown format for key Phase 2 tables.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $output = new ConsoleOutput();
        $report = "--- Data Quality Summary for LLM ---

";

        // --- Usahas Table ---
        $report .= "## Tabel: usahas (Data Supply)
";
        $usahaTotal = DB::table('usahas')->count();
        if ($usahaTotal > 0) {
            $latNull = DB::table('usahas')->whereNull('latitude')->count();
            $lonNull = DB::table('usahas')->whereNull('longitude')->count();
            $idsubslsNull = DB::table('usahas')->whereNull('idsubsls')->count();
            $kbliNull = DB::table('usahas')->whereNull('kbli')->orWhere('kbli', '')->count();
            $kbliInvalidLength = DB::table('usahas')->whereRaw('LENGTH(kbli) NOT IN (0, 5)')->count();
            $usahaSnippet = DB::table('usahas')->select('idsbr', 'nama_usaha', 'alamat', 'kbli', 'latitude', 'longitude', 'idsubsls')->take(3)->get();

            $report .= "- Total Records: {$usahaTotal}
";
            $report .= "- Latitude Null: {$latNull} (" . number_format(($latNull / $usahaTotal) * 100, 2) . "%)
";
            $report .= "- Longitude Null: {$lonNull} (" . number_format(($lonNull / $usahaTotal) * 100, 2) . "%)
";
            $report .= "- Idsubsls Null: {$idsubslsNull} (" . number_format(($idsubslsNull / $usahaTotal) * 100, 2) . "%)
";
            $report .= "- Kbli Null/Empty: {$kbliNull} (" . number_format(($kbliNull / $usahaTotal) * 100, 2) . "%)
";
            $report .= "- Kbli Invalid Length (not 5 digits): {$kbliInvalidLength} (" . number_format(($kbliInvalidLength / $usahaTotal) * 100, 2) . "%)
";
            $report .= "
### Snippet Data:
";
            $report .= "| idsbr | nama_usaha | alamat | kbli | latitude | longitude | idsubsls |
";
            $report .= "|---|---|---|---|---|---|---|
";
            foreach ($usahaSnippet as $row) {
                $report .= "| {$row->idsbr} | " . substr(str_replace('|', '', $row->nama_usaha), 0, 20) . " | " . substr(str_replace('|', '', $row->alamat), 0, 25) . " | {$row->kbli} | {$row->latitude} | {$row->longitude} | {$row->idsubsls} |
";
            }
        } else {
            $report .= "- Status: **Data Kosong**
";
        }
        $report .= "
---

";


        // --- Demografi SLS Table ---
        $report .= "## Tabel: demografi_sls (Data Demand)
";
        $demografiTotal = DB::table('demografi_sls')->count();
        if ($demografiTotal > 0) {
            $demografiSnippet = DB::table('demografi_sls')->take(3)->get();
            $report .= "- Total Records: {$demografiTotal}
";
            $report .= "
### Snippet Data:
";
            $report .= "| idsubsls | kode_desa | nama_desa | jumlah_penduduk | jumlah_kk |
";
            $report .= "|---|---|---|---|---|
";
            foreach ($demografiSnippet as $row) {
                $report .= "| {$row->idsubsls} | {$row->kode_desa} | {$row->nama_desa} | {$row->jumlah_penduduk} | {$row->jumlah_kk} |
";
            }
        } else {
            $report .= "- Status: **Data Kosong**
";
        }
        $report .= "
---

";


        // --- Peta SLS Table ---
        $report .= "## Tabel: peta_sls (Data Spasial)
";
        $petaTotal = DB::table('peta_sls')->count();
        if ($petaTotal > 0) {
            $petaSnippet = DB::table('peta_sls')->select('idsubsls')->take(3)->get();
            $report .= "- Total Records: {$petaTotal}
";
            $report .= "- Catatan: Kolom 'geom' berisi data poligon dan tidak ditampilkan di snippet.
";
            $report .= "
### Snippet Data:
";
            $report .= "| idsubsls |
";
            $report .= "|---|
";
            foreach ($petaSnippet as $row) {
                $report .= "| {$row->idsubsls} |
";
            }
        } else {
            $report .= "- Status: **Data Kosong**
";
        }
        $report .= "
---

";


        // --- Referensi KBLI Table ---
        $report .= "## Tabel: referensi_kbli (Data Kamus)
";
        $kbliTotal = DB::table('referensi_kbli')->count();
        if ($kbliTotal > 0) {
            $kbliSnippet = DB::table('referensi_kbli')->take(3)->get();
            $report .= "- Total Records: {$kbliTotal}
";
            $report .= "
### Snippet Data:
";
            $report .= "| kbli_5_digit | judul_kbli | deskripsi_kbli |
";
            $report .= "|---|---|---|
";
            foreach ($kbliSnippet as $row) {
                $report .= "| {$row->kbli_5_digit} | " . substr(str_replace('|', '', $row->judul_kbli), 0, 30) . " | " . substr(str_replace('|', '', $row->deskripsi_kbli), 0, 40) . " |
";
            }
        } else {
            $report .= "- Status: **Data Kosong**
";
        }
        $report .= "
--- End of Summary ---";

        $output->writeln($report);

        return 0;
    }
}
