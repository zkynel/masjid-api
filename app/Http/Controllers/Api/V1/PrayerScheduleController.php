<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class PrayerScheduleController extends Controller
{
    private string $baseUrl = 'https://equran.id/api/v2/shalat';

    public function provinces()
    {
        $response = Http::acceptJson()->get($this->baseUrl . '/provinsi');

        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil daftar provinsi.',
                'data' => null,
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Daftar provinsi berhasil diambil.',
            'data' => $response->json('data'),
        ]);
    }

    public function cities(Request $request)
    {
        $validated = $request->validate([
            'provinsi' => ['required', 'string'],
        ]);

        $response = Http::acceptJson()->post($this->baseUrl . '/kabkota', [
            'provinsi' => $validated['provinsi'],
        ]);

        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil daftar kabupaten/kota.',
                'data' => null,
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Daftar kabupaten/kota berhasil diambil.',
            'data' => $response->json('data'),
        ]);
    }

    public function schedule(Request $request)
    {
        $validated = $request->validate([
            'provinsi' => ['required', 'string'],
            'kabkota' => ['required', 'string'],
            'bulan' => ['nullable', 'integer', 'min:1', 'max:12'],
            'tahun' => ['nullable', 'integer', 'min:2024'],
        ]);

        $response = Http::acceptJson()->post($this->baseUrl, [
            'provinsi' => $validated['provinsi'],
            'kabkota' => $validated['kabkota'],
            'bulan' => $validated['bulan'] ?? now()->month,
            'tahun' => $validated['tahun'] ?? now()->year,
        ]);

        if ($response->failed()) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil jadwal sholat.',
                'data' => null,
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Jadwal sholat berhasil diambil.',
            'data' => $response->json('data'),
        ]);
    }
}