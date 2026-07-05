<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash; // Tambahkan ini

class AuthController extends Controller
{
    public function login(Request $request)
    {

        // // TRICK DEBUG: Langsung kembalikan input apa adanya ke HP Vivo
        // return response()->json([
        //     'debug_status' => 'Membaca Input dari HP',
        //     'input_yang_diterima_laravel' => $request->all(),
        // ], 200);

        // // Kode di bawah ini biarkan saja dulu, tidak akan dieksekusi karena ada return di atas
        // // $request->validate([ ... ]);
    

        // 1. Validasi input
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        // 2. Cek User & Password (Menggunakan Hash)
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Email atau Password salah'
            ], 401);
        }

        // 3. Buat Token (Wajib agar Flutter bisa akses API lainnya)
        // Pastikan Anda sudah menjalankan 'php artisan migrate' untuk tabel personal_access_tokens
        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'token' => $token, // Flutter membutuhkan ini
            'user' => [
                'nama' => $user->nama, // Pastikan kuncinya 'nama'
                'role' => $user->role,
                'email' => $user->email
            ]
        ]);
    }

    // Fungsi untuk mengambil profil (Dipanggil fetchAllData di Flutter)
    public function userProfile(Request $request)
    {
        return response()->json($request->user());
    }

    // Fungsi Logout
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }
}