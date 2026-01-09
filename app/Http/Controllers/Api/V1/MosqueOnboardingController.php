<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Mosque;

class MosqueOnboardingController extends Controller
{
    private function currentMosque(Request $request): Mosque
    {
        $user = $request->user();

        $mosque = $user->mosques()->first();
        if (!$mosque) {
            abort(404, 'User belum terhubung dengan masjid.');
        }

        return $mosque;
    }

    // 1) Cek domain (slug) available
    public function checkDomain(Request $request)
    {
        $request->validate([
            'slug' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
        ]);

        $slug = Str::lower($request->slug);

        $exists = Mosque::where('slug', $slug)->exists();

        return response()->json([
            'success' => true,
            'data' => [
                'slug' => $slug,
                'available' => !$exists,
            ],
        ]);
    }

    // 2) Set domain (update slug)
    public function setDomain(Request $request)
    {
        $request->validate([
            'slug' => ['required', 'string', 'min:3', 'max:50', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
        ]);

        $mosque = $this->currentMosque($request);

        $slug = Str::lower($request->slug);

        $exists = Mosque::where('slug', $slug)
            ->where('id', '!=', $mosque->id)
            ->exists();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Slug sudah digunakan. Silakan pilih slug lain.',
            ], 422);
        }

        $mosque->slug = $slug;
        $mosque->save();

        return response()->json([
            'success' => true,
            'message' => 'Slug/domain berhasil disimpan.',
            'data' => [
                'mosque' => $mosque,
            ],
        ]);
    }

    // 3) List template (hardcode dulu biar cepat)
    public function listTemplates()
    {
        $templates = [
            [
                'code' => 'TEMPLATE_A',
                'name' => 'Template A (Hijau Minimal)',
                'description' => 'Layout sederhana: profil, program, berita, galeri.',
            ],
            [
                'code' => 'TEMPLATE_B',
                'name' => 'Template B (Modern Card)',
                'description' => 'Layout modern dengan kartu konten dan highlight kegiatan.',
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'templates' => $templates,
            ],
        ]);
    }

    // 4) Pilih template
    public function selectTemplate(Request $request)
    {
        $request->validate([
            'template_code' => ['required', 'string', 'in:TEMPLATE_A,TEMPLATE_B'],
        ]);

        $mosque = $this->currentMosque($request);

        $mosque->template_code = $request->template_code;
        $mosque->save();

        return response()->json([
            'success' => true,
            'message' => 'Template berhasil dipilih.',
            'data' => [
                'mosque' => $mosque,
            ],
        ]);
    }

    // 5) Accept Terms
    public function acceptTerms(Request $request)
    {
        $mosque = $this->currentMosque($request);

        $mosque->terms_accepted_at = now();
        $mosque->save();

        return response()->json([
            'success' => true,
            'message' => 'Syarat & ketentuan telah disetujui.',
            'data' => [
                'mosque' => $mosque,
            ],
        ]);
    }

    // 6) Submit Verification
    public function submitVerification(Request $request)
    {
        $mosque = $this->currentMosque($request);

        // Minimal check agar terasa “real”
        if (!$mosque->slug || !$mosque->template_code) {
            return response()->json([
                'success' => false,
                'message' => 'Lengkapi domain (slug) dan template terlebih dahulu.',
            ], 422);
        }

        if (!$mosque->terms_accepted_at) {
            return response()->json([
                'success' => false,
                'message' => 'Anda harus menyetujui syarat & ketentuan terlebih dahulu.',
            ], 422);
        }

        $mosque->verification_status = 'submitted';
        $mosque->verification_submitted_at = now();
        $mosque->verification_note = null;
        $mosque->save();

        // Catatan: pengiriman email bisa menyusul (opsional untuk deadline).
        // Untuk demo UAS, cukup tunjukkan status berubah menjadi "submitted".

        return response()->json([
            'success' => true,
            'message' => 'Verifikasi berhasil diajukan.',
            'data' => [
                'mosque' => $mosque,
            ],
        ]);
    }

    // 7) Status Verification
    public function verificationStatus(Request $request)
    {
        $mosque = $this->currentMosque($request);

        return response()->json([
            'success' => true,
            'data' => [
                'verification_status' => $mosque->verification_status,
                'submitted_at' => $mosque->verification_submitted_at,
                'verified_at' => $mosque->verified_at,
                'note' => $mosque->verification_note,
                'mosque' => $mosque,
            ],
        ]);
    }
}
