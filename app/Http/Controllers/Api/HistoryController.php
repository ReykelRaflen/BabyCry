<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\History;
use Illuminate\Support\Facades\Http; // Wajib untuk memanggil Python API
use Illuminate\Support\Facades\Storage;

class HistoryController extends Controller
{
    /**
     * 1. FUNGSI RIWAYAT TANGISAN (SINKRON & BEBAS DARI SALAH DETEKSI OWNER) [1]
     */
    public function index(Request $request)
    {
        try {
            $user = $request->user();

            if ($user->role === 'owner') {
                // Owner melihat semua riwayat beserta data bayi dan orang tua kandungnya
                $records = History::with(['baby.user'])->orderBy('created_at', 'desc')->get();
            } else {
                // Parent hanya melihat riwayat bayi miliknya sendiri secara aman [1.1.2]
                $records = History::whereHas('baby', function ($query) use ($user) {
                    $query->where('user_id', $user->id); // Menyaring kolom user_id di tabel babies [1.1.2]
                })
                    ->with(['baby.user'])
                    ->orderBy('created_at', 'desc')
                    ->get();
            }

            $formattedRecords = $records->map(function ($record) {
                $categoryMap = [
                    1 => 'Lelah',
                    2 => 'Sakit',
                    3 => 'Tidak Nyaman'
                ];

                $categoryName = $categoryMap[$record->cry_category_id] ?? 'Tidak Nyaman';

                // Menggunakan Null-Safe Operator (?->) agar 100% bebas crash jika datanya kosong [1]
                $parentName = $record->baby?->user?->nama ?? null;
                if (!$parentName) {
                    $parentName = 'Umum (Tanpa Login)';
                }

                return [
                    'id' => $record->id,
                    'baby_id' => $record->baby_id,
                    'user_id' => $record->user_id,
                    'cry_category_id' => $record->cry_category_id,
                    'category_name' => $categoryName,
                    'categoryName' => $categoryName,
                    'confidence' => $record->confidence . '%',
                    'audio_path' => $record->audio_path,
                    'created_at' => $record->created_at->format('Y-m-d H:i:s'),
                    'createdAt' => $record->created_at->format('Y-m-d H:i:s'),

                    'baby_name' => $record->baby?->nama ?? 'Tamu / Tanpa Nama',
                    'babyName' => $record->baby?->nama ?? 'Tamu / Tanpa Nama',
                    'parent_name' => $parentName,
                    'parentName' => $parentName,
                ];
            });

            return response()->json($formattedRecords);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal memuat riwayat: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * 2. FUNGSI PENYIMPANAN: Deteksi Tangisan AI
     */
    public function store(Request $request)
    {
        $request->validate([
            'audio' => 'required|file',
        ]);

        $path = $request->file('audio')->store('recordings', 'public');
        $fullPath = storage_path('app/public/' . $path);

        $labelAi = 'tidak_terdeteksi';
        $confidenceStr = '0.00%';
        $confidenceVal = 0.0;

        try {
            $response = Http::attach(
                'audio',
                file_get_contents($fullPath),
                'audio.wav'
            )->timeout(15)->post('https://reykelraflen-space-ai.hf.space/predict');

            if ($response->successful()) {
                $ai = $response->json();

                $labelAi = $ai['hasil'] ?? 'tidak_nyaman';
                $confidenceStr = $ai['akurasi'] ?? '0.00%';
                $confidenceVal = floatval(str_replace('%', '', $confidenceStr));
            } else {
                throw new \Exception("Python API mengembalikan status: " . $response->status());
            }
        } catch (\Exception $e) {
            \Log::error("Gagal konek AI: " . $e->getMessage());
        }

        $categoryMap = ['lelah' => 1, 'sakit' => 2, 'tidak_nyaman' => 3];
        $categoryId = $categoryMap[$labelAi] ?? 3;

        $history = new History();
        $history->baby_id = ($request->baby_id == 0) ? null : $request->baby_id;
        $history->user_id = ($request->user_id == 0) ? null : $request->user_id;
        $history->cry_category_id = $categoryId;
        $history->confidence = $confidenceVal;
        $history->audio_path = $path;
        $history->save();

        return response()->json([
            'status' => 'success',
            'category' => ucfirst($labelAi),
            'confidence' => $confidenceStr,
            'recommendation' => $this->getRecommendation($labelAi)
        ]);
    }

    /**
     * 3. FUNGSI STATISTIK (SINKRON & BEBAS DARI SALAH DETEKSI OWNER) [1]
     */
    public function getAdvancedStats(Request $request)
    {
        try {
            $user = $request->user();
            $period = $request->query('period', 'day');

            $startDate = now();
            if ($period === 'day') {
                $startDate = now()->startOfDay();
            } elseif ($period === 'week') {
                $startDate = now()->subDays(7)->startOfDay();
            } elseif ($period === 'month') {
                $startDate = now()->subMonths(1)->startOfDay();
            }

            $query = History::where('created_at', '>=', $startDate);

            // Filter statistik berdasarkan kepemilikan bayi di tabel 'babies' [1.1.2]
            if ($user->role === 'parent') {
                $query->whereHas('baby', function ($q) use ($user) {
                    $q->where('user_id', $user->id); // Hanya menghitung statistik bayi milik orang tua ini [1.1.2]
                });
            }

            $histories = $query->get();

            $total = $histories->count();

            $classification = [
                'lelah' => $histories->where('cry_category_id', 1)->count(),
                'sakit' => $histories->where('cry_category_id', 2)->count(),
                'tidak_nyaman' => $histories->where('cry_category_id', 3)->count(),
            ];

            $peakHour = "00:00";
            if ($total > 0) {
                $hours = $histories->groupBy(function ($item) {
                    return $item->created_at->format('H') . ':00';
                });

                $maxCount = 0;
                foreach ($hours as $hour => $items) {
                    if ($items->count() > $maxCount) {
                        $maxCount = $items->count();
                        $peakHour = $hour;
                    }
                }
            }

            // Data Deteksi per-Bayi (Untuk Tampilan Orang Tua) [1]
            $perBaby = [];
            if ($user->role === 'parent') {
                // Memuat relasi baby dan orang tua kandungnya secara aman [1]
                $histories->load(['baby.user']);
                $groupedByBaby = $histories->groupBy('baby_id');
                foreach ($groupedByBaby as $babyId => $items) {
                    $babyName = $items->first()->baby?->nama ?? 'Tanpa Nama';
                    $parentName = $items->first()->baby?->user?->nama ?? 'Orang Tua';

                    // Gabungkan nama bayi & nama orang tua kandungnya agar ter-render indah di HP [1]
                    $combinedName = "$babyName (Orang Tua: $parentName)";
                    $perBaby[$combinedName] = $items->count();
                }
            }

            // Data Deteksi Terperinci (Untuk Tampilan Owner/Admin) [1]
            $detailedStats = [];
            if ($user->role === 'owner') {
                $histories->load(['baby.user']);

                // Kelompokkan berdasarkan ID orang tua asli milik si bayi (bukan pencatatnya!) [1.1.2, 1.2.2]
                $groupedByParent = $histories->groupBy(function ($history) {
                    return $history->baby?->user_id ?? 0;
                });

                foreach ($groupedByParent as $parentUserId => $parentHistories) {
                    $firstHistory = $parentHistories->first();
                    $parentName = $firstHistory->baby?->user?->nama ?? 'Tamu Anonim';

                    $babiesList = [];
                    $groupedByBaby = $parentHistories->groupBy('baby_id');
                    foreach ($groupedByBaby as $babyId => $babyHistories) {
                        $babiesList[] = [
                            'nama_bayi' => $babyHistories->first()->baby?->nama ?? 'Tanpa Nama',
                            'count' => $babyHistories->count()
                        ];
                    }

                    $detailedStats[] = [
                        'nama_orang_tua' => $parentName,
                        'babies' => $babiesList
                    ];
                }
            }

            return response()->json([
                'total' => $total,
                'classification' => $classification,
                'peak_hour' => $peakHour,
                'per_baby' => (object)$perBaby,
                'detailed_stats' => $detailedStats
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal memuat statistik: ' . $e->getMessage()
            ], 500);
        }
    }

    private function getRecommendation($label)
    {
        $list = [
            'lelah' => 'Bayi Anda mengantuk. Redupkan lampu dan buat suasana tenang.',
            'sakit' => 'Bayi mungkin merasa sakit/kembung. Coba usap punggungnya.',
            'tidak_nyaman' => 'Periksa popok bayi atau suhu ruangan.'
        ];
        return $list[$label] ?? 'Periksa kenyamanan bayi Anda.';
    }

    /**
     * 4. FUNGSI BARU: Mengambil Ringkasan Statistik Harian Dashboard [1]
     */
    public function getDailyStats(Request $request)
    {
        try {
            $user = $request->user();

            // Ambil data hari ini saja (sejak pukul 00:00 hari ini) [1]
            $startDate = now()->startOfDay();

            $query = History::where('created_at', '>=', $startDate);

            // Filter berdasarkan kepemilikan bayi jika yang login adalah Parent (Orang Tua) [1]
            if ($user->role === 'parent') {
                $query->whereHas('baby', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            }

            $histories = $query->get();
            $total = $histories->count();

            // Hitung kategori tangisan terbanyak hari ini [1]
            $mostFrequent = '-';
            if ($total > 0) {
                $counts = [
                    'lelah' => $histories->where('cry_category_id', 1)->count(),
                    'sakit' => $histories->where('cry_category_id', 2)->count(),
                    'tidak_nyaman' => $histories->where('cry_category_id', 3)->count(),
                ];

                // Urutkan dari jumlah terbanyak [1]
                arsort($counts);
                $mostFrequent = key($counts); // Ambil kategori teratas [1]
            }

            return response()->json([
                'total' => $total,
                'most_frequent' => $mostFrequent
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal memuat daily stats: ' . $e->getMessage()
            ], 500);
        }
    }
}
