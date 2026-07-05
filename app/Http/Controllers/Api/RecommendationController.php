<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recommendation;
use Illuminate\Http\Request;

class RecommendationController extends Controller
{
    public function index()
    {
        return response()->json(
            Recommendation::with('category')->get()
        );
    }

    public function store(Request $request)
    {
        $recommendation = Recommendation::create([
            'cry_category_id' => $request->cry_category_id,
            'rekomendasi' => $request->rekomendasi
        ]);

        return response()->json([
            'message' => 'Rekomendasi berhasil ditambahkan',
            'data' => $recommendation
        ]);
    }

    public function update(Request $request, $id)
    {
        $recommendation = Recommendation::findOrFail($id);

        $recommendation->update([
            'rekomendasi' => $request->rekomendasi
        ]);

        return response()->json([
            'message' => 'Rekomendasi berhasil diperbarui'
        ]);
    }

    public function destroy($id)
    {
        Recommendation::findOrFail($id)->delete();

        return response()->json([
            'message' => 'Rekomendasi berhasil dihapus'
        ]);
    }
}