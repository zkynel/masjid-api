<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class RegionController extends Controller
{
    private string $baseUrl = 'https://emsifa.github.io/api-wilayah-indonesia/api';

    public function provinces()
    {
        $data = Cache::remember('regions_provinces', now()->addDay(), function () {
            $response = Http::timeout(10)->get("{$this->baseUrl}/provinces.json");

            if ($response->failed()) {
                abort(500, 'Gagal mengambil data provinsi.');
            }

            return $response->json();
        });

        return response()->json([
            'success' => true,
            'message' => 'Daftar provinsi berhasil diambil.',
            'data' => $data,
        ]);
    }

    public function regencies(string $provinceId)
    {
        $data = Cache::remember("regions_regencies_{$provinceId}", now()->addDay(), function () use ($provinceId) {
            $response = Http::timeout(10)->get("{$this->baseUrl}/regencies/{$provinceId}.json");

            if ($response->failed()) {
                abort(500, 'Gagal mengambil data kota/kabupaten.');
            }

            return $response->json();
        });

        return response()->json([
            'success' => true,
            'message' => 'Daftar kota/kabupaten berhasil diambil.',
            'data' => $data,
        ]);
    }

    public function districts(string $regencyId)
    {
        $data = Cache::remember("regions_districts_{$regencyId}", now()->addDay(), function () use ($regencyId) {
            $response = Http::timeout(10)->get("{$this->baseUrl}/districts/{$regencyId}.json");

            if ($response->failed()) {
                abort(500, 'Gagal mengambil data kecamatan.');
            }

            return $response->json();
        });

        return response()->json([
            'success' => true,
            'message' => 'Daftar kecamatan berhasil diambil.',
            'data' => $data,
        ]);
    }

    public function villages(string $districtId)
    {
        $data = Cache::remember("regions_villages_{$districtId}", now()->addDay(), function () use ($districtId) {
            $response = Http::timeout(10)->get("{$this->baseUrl}/villages/{$districtId}.json");

            if ($response->failed()) {
                abort(500, 'Gagal mengambil data kelurahan/desa.');
            }

            return $response->json();
        });

        return response()->json([
            'success' => true,
            'message' => 'Daftar kelurahan/desa berhasil diambil.',
            'data' => $data,
        ]);
    }
}