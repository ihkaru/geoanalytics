<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usaha;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WilayahController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function kecamatan()
    {
        $kecamatans = Usaha::select('kdkec')
            ->distinct()
            ->whereNotNull('kdkec')
            ->orderBy('kdkec')
            ->get();
        return response()->json($kecamatans);
    }

    public function subslsGeojson()
    {
        $geojsonPath = base_path('data_geojson/Final_SLS_202416104.geojson');

        if (!file_exists($geojsonPath)) {
            return response()->json(['error' => 'GeoJSON file not found.'], 404);
        }

        $geojsonContent = file_get_contents($geojsonPath);
        return response($geojsonContent)->header('Content-Type', 'application/json');
    }

    public function getTiles(int $z, int $x, int $y)
    {
        $layerName = 'zona_peluang';

        $query = "
            WITH
            bounds AS (
                SELECT ST_TileEnvelope(?, ?, ?) AS geom
            ),
            mvt_geom AS (
                SELECT
                    p.idsubsls,
                    a.kbli_5_digit,
                    a.radius_meter,
                    a.skor_potensi,
                    a.zona,
                    ST_AsMVTGeom(
                        ST_Transform(p.geom, 3857),
                        bounds.geom
                    ) AS geom
                FROM
                    peta_sls p
                JOIN
                    analisis_zona_cache a ON p.idsubsls = a.idsubsls
                JOIN
                    bounds ON ST_Intersects(ST_Transform(p.geom, 3857), bounds.geom)
            )
            SELECT ST_AsMVT(mvt_geom.*, ?)
            FROM mvt_geom;
        ";

        $tile = DB::selectOne($query, [$z, $x, $y, $layerName]);
        
        // The result from ST_AsMVT is binary. It might be null, a string, or a resource stream.
        $tileData = null;
        if ($tile) {
            // Get the first property of the stdClass object, which holds our data
            $data = array_values((array)$tile)[0];
            if (is_resource($data)) {
                // If it's a resource stream, read it into a string
                $tileData = stream_get_contents($data);
            } else {
                // Otherwise, it's likely already a string or null
                $tileData = $data;
            }
        }

        // If tile data is null or empty after processing, return 204 No Content
        if (empty($tileData)) {
            return response('', 204);
        }

        return response($tileData)
            ->header('Content-Type', 'application/vnd.mapbox-vector-tile');
            // Note: Content-Encoding should be handled by Nginx/Apache if configured, not here.
            // ->header('Content-Encoding', 'gzip'); 
    }

    public function getZonaDetail(string $idsubsls)
    {
        // We need to query all analysis results for the given idsubsls,
        // as one SLS can have multiple results for different KBLI and radius combinations.
        $details = DB::table('analisis_zona_cache as a')
            ->leftJoin('muatan_subsls as m', 'a.idsubsls', '=', 'm.idsubsls')
            ->where('a.idsubsls', $idsubsls)
            ->select(
                'a.idsubsls',
                'm.nmdesa as nama_desa', // Get nama_desa from muatan_subsls
                'm.nmkec as nama_kecamatan', // Add nama_kecamatan
                'm.nmkab as nama_kabupaten', // Add nama_kabupaten
                'm.nmsls as nama_sls',       // Add nama_sls
                'a.kbli_5_digit',
                'a.radius_meter',
                'a.skor_potensi',
                'a.zona',
                'a.jumlah_penduduk_total',
                'a.jumlah_usaha_sejenis',
                'a.rasio_penduduk_per_usaha'
            )
            ->get();

        if ($details->isEmpty()) {
            // Return an empty array if no analysis data is found for this idsubsls
            return response()->json([]);
        }

        return response()->json($details);
    }

    public function getKbli()
    {
        // TEMPORARY FIX: Directly query the reference table to avoid dependency on analysis_zona_cache
        // This ensures the dropdown is always populated quickly.
        $kbliData = DB::table('referensi_kbli')
            ->whereRaw('LENGTH(kbli_id) = 5') // Ensure we only get 5-digit KBLI
            ->select('kbli_id', 'judul')
            ->orderBy('kbli_id')
            ->get();

        return response()->json($kbliData);
    }
}
