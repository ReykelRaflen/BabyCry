<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Baby;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class BabyController extends Controller
{
    /**
     * Menampilkan daftar bayi berdasarkan Role
     */
    public function index()
    {
        $user = Auth::user();

        // Jika login sebagai owner, tampilkan semua bayi beserta nama orang tuanya
        if ($user->role == 'owner') {
            $babies = Baby::with('user')->get();
        } else {
            // Jika login sebagai parent, tampilkan hanya bayi miliknya
            $babies = Baby::where('user_id', $user->id)->get();
        }

        return response()->json($babies);
    }

    /**
     * Menyimpan data bayi baru (Dipanggil oleh Owner)
     */
    public function store(Request $request)
    {
        // 1. Validasi Nama Kolom sesuai Tabel Anda
        $validator = Validator::make($request->all(), [
            'nama'          => 'required|string|max:255',
            'gender'        => 'required|in:Laki-laki,Perempuan',
            'date_of_birth' => 'required|date',
            'user_id'       => 'required|exists:users,id', // ID Orang Tua yang dipilih Owner
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        try {
            // 2. Simpan ke Database
            $baby = Baby::create([
                'nama'          => $request->nama,
                'gender'        => $request->gender,
                'date_of_birth' => $request->date_of_birth,
                'user_id'       => $request->user_id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Bayi berhasil didaftarkan ke akun orang tua',
                'data'    => $baby
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menampilkan detail satu bayi
     */
    public function show($id)
    {
        $baby = Baby::with('user')->find($id);

        if (!$baby) {
            return response()->json(['message' => 'Data bayi tidak ditemukan'], 404);
        }

        return response()->json($baby);
    }

    /**
     * Memperbarui data bayi
     */
    public function update(Request $request, $id)
    {
        $baby = Baby::find($id);

        if (!$baby) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $baby->update($request->only(['nama', 'gender', 'date_of_birth']));

        return response()->json([
            'success' => true,
            'message' => 'Data bayi berhasil diperbarui',
            'data'    => $baby
        ]);
    }

    /**
     * Menghapus data bayi
     */
    public function destroy($id)
    {
        $baby = Baby::find($id);

        if (!$baby) {
            return response()->json(['message' => 'Data tidak ditemukan'], 404);
        }

        $baby->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data bayi berhasil dihapus'
        ]);
    }
}