<?php

namespace Database\Seeders;

use App\Models\Mahasiswa;
use App\Models\Prodi;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $sistemInformasi = Prodi::firstOrCreate(['nama_prodi' => 'Sistem Informasi']);
        $informatika = Prodi::firstOrCreate(['nama_prodi' => 'Informatika']);

        Mahasiswa::firstOrCreate(['nim' => '0012026001'], [
            'id_prodi' => $sistemInformasi->id,
            'nama' => 'Andi Saputra',
        ]);
        Mahasiswa::firstOrCreate(['nim' => '0012026002'], [
            'id_prodi' => $informatika->id,
            'nama' => 'Siti Rahma',
        ]);
    }
}
