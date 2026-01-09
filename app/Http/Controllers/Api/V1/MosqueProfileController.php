<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Mosque;

class MosqueProfileController extends Controller
{
    private function userOwnsMosque(Request $request, Mosque $mosque): bool
    {
        return $request->user()
            ->mosques()
            ->where('mosques.id', $mosque->id)
            ->exists();
    }

    private function getMosqueOrFail(Request $request, string $slug): Mosque
    {
        $mosque = Mosque::where('slug', $slug)->firstOrFail();

        if (!$this->userOwnsMosque($request, $mosque)) {
            abort(403, 'Anda tidak memiliki akses ke masjid ini.');
        }

        return $mosque;
    }

    public function show(Request $request, string $slug)
    {
        $mosque = $this->getMosqueOrFail($request, $slug);

        return response()->json([
            'success' => true,
            'data' => [
                'mosque' => $mosque,
            ],
        ]);
    }

    public function update(Request $request, string $slug)
    {
        $mosque = $this->getMosqueOrFail($request, $slug);

        $validated = $request->validate([
            'name' => ['nullable', 'string', 'max:120'],
            'address' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'contact' => ['nullable', 'string', 'max:120'],
        ]);

        $mosque->fill(array_filter($validated, fn($v) => $v !== null));
        $mosque->save();

        return response()->json([
            'success' => true,
            'message' => 'Profil masjid berhasil diperbarui.',
            'data' => [
                'mosque' => $mosque,
            ],
        ]);
    }

    /**
     * Upload dokumen pendukung (minimal: logo).
     * Request: multipart/form-data
     * - logo (file) [jpg/png]
     */
    public function uploadDocuments(Request $request, string $slug)
    {
        $mosque = $this->getMosqueOrFail($request, $slug);

        $request->validate([
            'logo' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $path = $request->file('logo')->store('mosques/logos', 'public');

        $mosque->logo_path = $path;
        $mosque->save();

        return response()->json([
            'success' => true,
            'message' => 'Logo berhasil diupload.',
            'data' => [
                'logo_path' => $path,
                'logo_url' => Storage::disk('public')->url($path),
                'mosque' => $mosque,
            ],
        ]);
    }
}
