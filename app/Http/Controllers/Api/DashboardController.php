<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CryRecord;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function stats()
    {
        $totalTangisan = CryRecord::count();

        $rataDurasi = CryRecord::avg('duration');

        $penyebabTerbanyak = CryRecord::select(
            'cry_category_id',
            DB::raw('COUNT(*) as total')
        )
            ->groupBy('cry_category_id')
            ->orderByDesc('total')
            ->first();

        return response()->json([
            'total_tangisan' => $totalTangisan,
            'durasi_rata_rata' => round($rataDurasi ?? 0, 2),
            'penyebab_terbanyak' => $penyebabTerbanyak
        ]);
    }

    public function getAdvancedStats(Request $request)
    {
        $user = auth()->user();
        $period = $request->query('period', 'day'); // day, week, month

        // Query Dasar
        $query = CryRecord::with('category', 'baby.user');

        // Filter Berdasarkan Role
        if ($user->role == 'parent') {
            $babyIds = Baby::where('user_id', $user->id)->pluck('id');
            $query->whereIn('baby_id', $babyIds);
        }

        // Filter Berdasarkan Waktu
        if ($period == 'week')
            $query->where('created_at', '>=', now()->subDays(7));
        if ($period == 'month')
            $query->where('created_at', '>=', now()->subMonth());
        if ($period == 'day')
            $query->whereDate('created_at', now());

        $records = $query->get();

        // 1. Data Klasifikasi (lelah, sakit, tidak_nyaman)
        $classification = $records->groupBy('category.nama')->map->count();

        // 2. Data per Orang Tua (Hanya untuk Owner)
        $perParent = [];
        if ($user->role == 'owner') {
            $perParent = $records->groupBy('baby.user.nama')->map->count();
        }

        // 3. Data per Bayi (Untuk Parent & Owner)
        $perBaby = $records->groupBy('baby.nama')->map->count();

        return response()->json([
            'total' => $records->count(),
            'classification' => $classification,
            'per_parent' => $perParent,
            'per_baby' => $perBaby,
        ]);
    }

}