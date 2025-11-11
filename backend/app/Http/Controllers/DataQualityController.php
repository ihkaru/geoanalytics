<?php

namespace App\Http\Controllers;

use App\Models\Usaha;
use App\Models\MuatanSubsls;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DataQualityController extends Controller
{
    public function usahaCheck()
    {
        $totalRecords = Usaha::count();

        // Basic Completeness Stats
        $stats = [
            'total_records' => $totalRecords,
            'latitude_null' => Usaha::whereNull('latitude')->count(),
            'longitude_null' => Usaha::whereNull('longitude')->count(),
            'idsubsls_null' => Usaha::whereNull('idsubsls')->orWhere('idsubsls', '')->count(),
            'kbli_null' => Usaha::whereNull('kbli')->count(),
            'kbli_invalid_length' => Usaha::where(DB::raw('LENGTH(kbli)'), '!=', 5)->count(),
        ];

        // Deeper Analysis
        $advancedStats = [
            'lat_and_idsubsls_null' => Usaha::whereNull('latitude')
                ->where(function ($query) {
                    $query->whereNull('idsubsls')->orWhere('idsubsls', '');
                })
                ->count(),
            'geocoding_potential' => Usaha::whereNull('latitude')
                ->whereNotNull('alamat')
                ->where('alamat', '!=', '')
                ->count(),
            'rescuable_by_idsubsls' => Usaha::whereNull('latitude')
                ->whereNotNull('idsubsls')
                ->where('idsubsls', '!=', '')
                ->whereRaw('LENGTH(idsubsls) = 16')
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                          ->from('muatan_subsls')
                          ->whereColumn('muatan_subsls.idsubsls', 'usahas.idsubsls');
                })
                ->count(),
            'enrichable_by_wilayah' => Usaha::where(function ($query) {
                    $query->whereNull('idsubsls')->orWhere('idsubsls', '');
                })
                ->whereNotNull('kdkec')
                ->whereNotNull('kddesa')
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                          ->from('muatan_subsls')
                          ->whereColumn('muatan_subsls.kdkec', 'usahas.kdkec')
                          ->whereColumn('muatan_subsls.kddesa', 'usahas.kddesa');
                })
                ->count(),
        ];

        // Calculate average_idsubsls_per_wilayah_for_enrichable
        $averageIdsubslsPerWilayah = DB::table('usahas')
            ->select(DB::raw('AVG(subsls_count) as average_count'))
            ->joinSub(function ($query) {
                $query->select('kdkec', 'kddesa', DB::raw('COUNT(DISTINCT idsubsls) as subsls_count'))
                      ->from('muatan_subsls')
                      ->groupBy('kdkec', 'kddesa');
            }, 'muatan_subsls_counts', function ($join) {
                $join->on('usahas.kdkec', '=', 'muatan_subsls_counts.kdkec')
                     ->on('usahas.kddesa', '=', 'muatan_subsls_counts.kddesa');
            })
            ->where(function ($query) {
                $query->whereNull('usahas.idsubsls')->orWhere('usahas.idsubsls', '');
            })
            ->whereNotNull('usahas.kdkec')
            ->whereNotNull('usahas.kddesa')
            ->value('average_count');

        $advancedStats['average_idsubsls_per_wilayah_for_enrichable'] = $averageIdsubslsPerWilayah ?? 0;

        // Data Snippets for Analysis
        $invalidKbliSamples = Usaha::select('kbli', DB::raw('count(*) as total'))
            ->where(DB::raw('LENGTH(kbli)'), '!=', 5)
            ->groupBy('kbli')
            ->orderBy('total', 'desc')
            ->limit(10)
            ->get();

        $geocodingAddressSamples = Usaha::select('alamat')
            ->whereNull('latitude')
            ->whereNotNull('alamat')
            ->where('alamat', '!=', '')
            ->limit(10)
            ->get();

        $rescuableSamples = Usaha::select('idsbr', 'nama_usaha', 'idsubsls', 'alamat')
            ->whereNull('latitude')
            ->whereNotNull('idsubsls')
            ->where('idsubsls', '!=', '')
            ->whereRaw('LENGTH(idsubsls) = 16')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('muatan_subsls')
                      ->whereColumn('muatan_subsls.idsubsls', 'usahas.idsubsls');
            })
            ->limit(10)
            ->get();

        $enrichableSamples = Usaha::select('idsbr', 'nama_usaha', 'kdkec', 'kddesa', 'alamat')
            ->where(function ($query) {
                $query->whereNull('idsubsls')->orWhere('idsubsls', '');
            })
            ->whereNotNull('kdkec')
            ->whereNotNull('kddesa')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('muatan_subsls')
                      ->whereColumn('muatan_subsls.kdkec', 'usahas.kdkec')
                      ->whereColumn('muatan_subsls.kddesa', 'usahas.kddesa');
            })
            ->limit(10)
            ->get()
            ->map(function ($usaha) {
                $potential_idsubsls_list = MuatanSubsls::where('kdkec', $usaha->kdkec)
                    ->where('kddesa', $usaha->kddesa)
                    ->distinct()
                    ->pluck('idsubsls');

                $count = $potential_idsubsls_list->count();
                if ($count > 3) {
                    $usaha->potential_idsubsls = $potential_idsubsls_list->take(3)->implode(', ') . " (... " . ($count - 3) . " more)";
                } else {
                    $usaha->potential_idsubsls = $potential_idsubsls_list->implode(', ');
                }
                return $usaha;
            });


        // Existing Aggregations
        $topKbli = Usaha::select('kbli', DB::raw('count(*) as total'))
            ->groupBy('kbli')
            ->orderBy('total', 'desc')
            ->limit(15)
            ->get();

        $statusUsaha = Usaha::select('status_usaha', DB::raw('count(*) as total'))
            ->groupBy('status_usaha')
            ->orderBy('total', 'desc')
            ->get();

        return view('data-quality.usaha', [
            'stats' => array_merge($stats, $advancedStats),
            'topKbli' => $topKbli,
            'statusUsaha' => $statusUsaha,
            'invalidKbliSamples' => $invalidKbliSamples,
            'geocodingAddressSamples' => $geocodingAddressSamples,
            'rescuableSamples' => $rescuableSamples,
            'enrichableSamples' => $enrichableSamples,
        ]);
    }
}
