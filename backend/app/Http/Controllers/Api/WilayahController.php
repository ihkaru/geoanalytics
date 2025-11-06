<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Usaha;
use Illuminate\Http\Request;

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
}
