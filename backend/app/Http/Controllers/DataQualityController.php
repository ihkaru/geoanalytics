<?php

namespace App\Http\Controllers;

use App\Models\Usaha;
use App\Models\MuatanSubsls;
use App\Models\DemografiSls;
use App\Models\PetaSls;
use App\Models\ReferensiKbli;
use App\Models\Regsosek;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DataQualityController extends Controller {
    public function usahaCheck() {
        // 1. BASIC STATS - tetap lightweight
        $totalRecords = Usaha::count();
        $stats = [
            'total_records' => $totalRecords,
            'latitude_null' => Usaha::whereNull('latitude')->count(),
            'longitude_null' => Usaha::whereNull('longitude')->count(),
            'idsubsls_null' => Usaha::whereNull('idsubsls')->orWhere('idsubsls', '')->count(),
            'kbli_null' => Usaha::whereNull('kbli')->count(),
            'kbli_invalid_length' => Usaha::whereRaw('LENGTH(kbli) != 5')->count(),
        ];

        // 2. ADVANCED STATS
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
        ];

        // 3. RESCUABLE DATA
        $rescuableCount = Usaha::join('muatan_subsls', 'usahas.idsubsls', '=', 'muatan_subsls.idsubsls')
            ->whereNull('usahas.latitude')
            ->whereNotNull('usahas.idsubsls')
            ->whereRaw('LENGTH(usahas.idsubsls) = 16')
            ->distinct()
            ->count('usahas.idsbr');
        $advancedStats['rescuable_by_idsubsls'] = $rescuableCount;

        // 4. ENRICHEABLE DATA
        $enrichableCount = Usaha::join('muatan_subsls', function ($join) {
            $join->on('usahas.kdkec', '=', 'muatan_subsls.kdkec')
                ->on('usahas.kddesa', '=', 'muatan_subsls.kddesa');
        })
            ->where(function ($query) {
                $query->whereNull('usahas.idsubsls')->orWhere('usahas.idsubsls', '');
            })
            ->whereNotNull('usahas.kdkec')
            ->whereNotNull('usahas.kddesa')
            ->distinct()
            ->count('usahas.idsbr');
        $advancedStats['enrichable_by_wilayah'] = $enrichableCount;

        // 5. AVERAGE IDSUBSLS
        $avg = MuatanSubsls::select(DB::raw('AVG(count_per_wilayah) as avg'))
            ->from(function ($sub) {
                $sub->select('kdkec', 'kddesa', DB::raw('COUNT(DISTINCT idsubsls) as count_per_wilayah'))
                    ->from('muatan_subsls')
                    ->groupBy('kdkec', 'kddesa');
            }, 'wilayah_counts')
            ->value('avg');
        $averageIdsubslsPerWilayah = round($avg ?? 0, 2);
        $advancedStats['average_idsubsls_per_wilayah_for_enrichable'] = $averageIdsubslsPerWilayah;

        // 6. DATA SAMPLES
        $invalidKbliSamples = Usaha::select('kbli', DB::raw('COUNT(*) as total'))
            ->whereRaw('LENGTH(kbli) != 5')
            ->groupBy('kbli')
            ->orderByDesc('total')
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
            ->whereRaw('LENGTH(idsubsls) = 16')
            ->whereExists(function ($query) {
                $query->from('muatan_subsls')
                    ->whereColumn('muatan_subsls.idsubsls', 'usahas.idsubsls');
            })
            ->limit(10)
            ->get();

        // 7. ENRICHEABLE SAMPLES - FIX N+1 Query
        $enrichableSamples = Usaha::select('idsbr', 'nama_usaha', 'kdkec', 'kddesa', 'alamat')
            ->where(function ($query) {
                $query->whereNull('idsubsls')->orWhere('idsubsls', '');
            })
            ->whereNotNull('kdkec')
            ->whereNotNull('kddesa')
            ->whereExists(function ($query) {
                $query->from('muatan_subsls')
                    ->whereColumn('muatan_subsls.kdkec', 'usahas.kdkec')
                    ->whereColumn('muatan_subsls.kddesa', 'usahas.kddesa');
            })
            ->limit(10)
            ->get();

        $wilayahList = $enrichableSamples->map(function ($item) {
            return ['kdkec' => $item->kdkec, 'kddesa' => $item->kddesa];
        })->unique()->values();

        $subslsMap = [];
        if ($wilayahList->isNotEmpty()) {
            $subslsData = MuatanSubsls::whereIn('kdkec', $wilayahList->pluck('kdkec'))
                ->whereIn('kddesa', $wilayahList->pluck('kddesa'))
                ->select('kdkec', 'kddesa', 'idsubsls')
                ->distinct()
                ->get()
                ->groupBy(['kdkec', 'kddesa']);

            foreach ($subslsData as $kdkec => $desas) {
                foreach ($desas as $kddesa => $idsubslsList) {
                    $subslsMap[$kdkec][$kddesa] = $idsubslsList->pluck('idsubsls');
                }
            }
        }

        $enrichableSamples->transform(function ($usaha) use ($subslsMap) {
            $idsubslsList = $subslsMap[$usaha->kdkec][$usaha->kddesa] ?? collect([]);
            $count = $idsubslsList->count();

            if ($count > 3) {
                $usaha->potential_idsubsls = $idsubslsList->slice(0, 3)->implode(', ') .
                    " (... " . ($count - 3) . " more)";
            } else {
                $usaha->potential_idsubsls = $idsubslsList->implode(', ');
            }
            return $usaha;
        });

        // 8. TOP DATA
        $topKbli = Usaha::select('kbli', DB::raw('COUNT(*) as total'))
            ->groupBy('kbli')
            ->orderByDesc('total')
            ->limit(15)
            ->get();

        $statusUsaha = Usaha::select('status_usaha', DB::raw('COUNT(*) as total'))
            ->groupBy('status_usaha')
            ->orderByDesc('total')
            ->get();

        // 9. OTHER MODELS
        $demografiSlsData = [
            'total' => DemografiSls::count(),
            'attributes' => (new DemografiSls())->getFillable(),
            'snippet' => DemografiSls::take(3)->get()->toArray(),
        ];

        $petaSlsData = [
            'total' => PetaSls::count(),
            'attributes' => (new PetaSls())->getFillable(),
            'snippet' => PetaSls::select('idsubsls')->take(3)->get()->toArray(),
        ];

        $referensiKbliData = [
            'total' => ReferensiKbli::count(),
            'attributes' => (new ReferensiKbli())->getFillable(),
            'snippet' => ReferensiKbli::take(3)->get()->toArray(),
        ];

        // Re-added Regsosek with fast estimate
        $regsosekTotalEstimate = DB::table('pg_class')->where('relname', 'regsosek')->value('reltuples');
        $regsosekData = [
            'total' => $regsosekTotalEstimate,
            'attributes' => (new Regsosek())->getFillable(),
            'snippet' => Regsosek::select('kode_prov', 'kode_kab', 'id_rt', 'r401', 'r407')->take(3)->get()->toArray(),
        ];

        return view('data-quality.usaha', [
            'stats' => array_merge($stats, $advancedStats),
            'topKbli' => $topKbli,
            'statusUsaha' => $statusUsaha,
            'invalidKbliSamples' => $invalidKbliSamples,
            'geocodingAddressSamples' => $geocodingAddressSamples,
            'rescuableSamples' => $rescuableSamples,
            'enrichableSamples' => $enrichableSamples,
            'demografiSlsData' => $demografiSlsData,
            'petaSlsData' => $petaSlsData,
            'referensiKbliData' => $referensiKbliData,
            'regsosekData' => $regsosekData,
        ]);
    }
}