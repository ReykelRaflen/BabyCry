<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\CryCategory;
use App\Models\Recommendation;
use Illuminate\Support\Facades\Hash;

class MasterSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat User Awal (Owner)
        User::updateOrCreate(
            ['email' => 'owner@babycry.com'],
            [
                'nama' => 'Owner BabyCry AI',
                'password' => 'admin123',
                'role' => 'owner',
            ]
        );

        // 2. Buat Kategori Tangisan (Sesuai Class AI Anda)
        $tidakNyaman = CryCategory::updateOrCreate(['nama' => 'tidak_nyaman']);
        $sakit = CryCategory::updateOrCreate(['nama' => 'sakit']);
        $lelah = CryCategory::updateOrCreate(['nama' => 'lelah']);

        // 3. Buat Rekomendasi Solusi untuk tiap Kategori
        
        // Rekomendasi: Tidak Nyaman
        Recommendation::updateOrCreate(
            ['cry_category_id' => $tidakNyaman->id],
            [
                'isi' => 'Si kecil merasa tidak nyaman. Periksa popoknya apakah sudah penuh, pastikan suhu ruangan tidak terlalu panas/dingin, dan pastikan pakaian bayi tidak terlalu ketat.'
            ]
        );

        // Rekomendasi: Sakit
        Recommendation::updateOrCreate(
            ['cry_category_id' => $sakit->id],
            [
                'isi' => 'Tangisan ini mengindikasikan rasa sakit. Coba periksa suhu tubuhnya. Jika si kecil menangis dengan nada tinggi secara terus-menerus, segera hubungi dokter atau bidan terdekat.'
            ]
        );

        // Rekomendasi: Lelah
        Recommendation::updateOrCreate(
            ['cry_category_id' => $lelah->id],
            [
                'isi' => 'Si kecil sedang kelelahan dan ingin tidur. Kurangi kebisingan di sekitar, redupkan lampu ruangan, dan ayun si kecil dengan lembut sampai ia terlelap.'
            ]
        );

        $this->command->info('Master Data (Owner, Categories, Recommendations) berhasil dimasukkan!');
    }
}