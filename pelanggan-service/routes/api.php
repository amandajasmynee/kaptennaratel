<?php

use App\Http\Controllers\Api\PelangganController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Http;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// === DASHBOARD
Route::get('/dashboard',       [PelangganController::class, 'dashboard']);
Route::get('/dashboard/week',  [PelangganController::class, 'dashboardWeek']);
Route::get('/dashboard/aggregate', [PelangganController::class, 'dashboardAggregate']);

// RESTful route untuk resource pelanggan
Route::apiResource('pelanggan', PelangganController::class);

// Proxy ke unit-service (GET all units)
Route::get('/external/units', function () {
    $response = Http::get('http://localhost:8001/api/units');
    return response()->json($response->json(), $response->status());
});

// Proxy ke harga-paket-service (GET all harga paket)
Route::get('/external/harga-paket', function () {
    $response = Http::get('http://localhost:8002/api/ref-harga-paket');
    return response()->json($response->json(), $response->status());
});

// Route default untuk auth user (jika pakai Sanctum)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
