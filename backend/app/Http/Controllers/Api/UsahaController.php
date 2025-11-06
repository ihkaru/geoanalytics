<?php

namespace App\Http\Controllers\Api;

use App\Models\Usaha;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UsahaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Usaha::query();

        // Search Filter
        if ($request->has('searchQuery') && $request->input('searchQuery') != '') {
            $query->where('nama_usaha', 'ilike', '%' . $request->input('searchQuery') . '%');
        }

        // Bounding Box Filter
        if ($request->has(['north', 'south', 'east', 'west'])) {
            $query->where('latitude', '>=', $request->input('south'));
            $query->where('latitude', '<=', $request->input('north'));
            $query->where('longitude', '>=', $request->input('west'));
            $query->where('longitude', '<=', $request->input('east'));
        }

        return $query->limit(3000)->get();
    }

    public function searchSuggestions(Request $request)
    {
        $query = $request->input('query');
        if (!$query) {
            return response()->json([]);
        }

        $suggestions = Usaha::where('nama_usaha', 'ilike', '%' . $query . '%')
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->select('idsbr', 'nama_usaha', 'alamat', 'latitude', 'longitude') // Select only needed fields
            ->limit(10)
            ->get();

        return response()->json($suggestions);
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
}
