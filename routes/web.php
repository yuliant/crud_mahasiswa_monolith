<?php

use App\Http\Controllers\MahasiswaController;
use App\Http\Controllers\ProdiController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::redirect('/', '/mahasiswa');

Route::get('/prodi/{prodi}/hapus', [ProdiController::class, 'confirmDelete'])->name('prodi.delete');
Route::resource('prodi', ProdiController::class)->except('show')->parameters(['prodi' => 'prodi']);

Route::get('/mahasiswa/{mahasiswa}/hapus', [MahasiswaController::class, 'confirmDelete'])->name('mahasiswa.delete');
Route::resource('mahasiswa', MahasiswaController::class)->except('show')->parameters(['mahasiswa' => 'mahasiswa']);
