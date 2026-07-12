<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Mosque;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminVerificationController extends Controller
{
    /**
     * Dashboard statistik pengajuan verifikasi.
     * GET /api/v1/admin/dashboard
     */
    public function dashboard()
    {
        $stats = [
            'total'    => Mosque::whereNotNull('verification_submitted_at')->count(),
            'pending'  => Mosque::where('verification_status', 'submitted')->count(),
            'verified' => Mosque::where('verification_status', 'verified')->count(),
            'rejected' => Mosque::where('verification_status', 'rejected')->count(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Dashboard statistik verifikasi.',
            'data'    => $stats,
        ]);
    }

    /**
     * Daftar pengajuan verifikasi (paginated, filterable, searchable).
     * GET /api/v1/admin/verifications?status=submitted&search=al-ikhlas&per_page=15
     */
    public function index(Request $request)
    {
        $query = Mosque::query()
            ->whereNotNull('verification_submitted_at');

        // Filter berdasarkan status
        if ($request->filled('status') && $request->status !== 'all') {
            $request->validate([
                'status' => ['in:draft,submitted,verified,rejected'],
            ]);
            $query->where('verification_status', $request->status);
        }

        // Search berdasarkan nama masjid atau slug
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Sort: default terbaru dulu
        $sortBy = $request->get('sort_by', 'verification_submitted_at');
        $sortDir = $request->get('sort_dir', 'desc');

        $allowedSorts = ['verification_submitted_at', 'name', 'verification_status', 'created_at'];
        if (!in_array($sortBy, $allowedSorts)) {
            $sortBy = 'verification_submitted_at';
        }

        $query->orderBy($sortBy, $sortDir === 'asc' ? 'asc' : 'desc');

        // Paginate
        $perPage = min((int) $request->get('per_page', 15), 50);
        $mosques = $query->paginate($perPage);

        // Transform: tambahkan URL dokumen
        $mosques->getCollection()->transform(function ($mosque) {
            $mosque->waqf_imb_document_url = $mosque->waqf_imb_document_path
                ? Storage::disk('public')->url($mosque->waqf_imb_document_path)
                : null;
            $mosque->management_decree_document_url = $mosque->management_decree_document_path
                ? Storage::disk('public')->url($mosque->management_decree_document_path)
                : null;
            return $mosque;
        });

        return response()->json([
            'success' => true,
            'message' => 'Daftar pengajuan verifikasi.',
            'data'    => $mosques,
        ]);
    }

    /**
     * Detail pengajuan verifikasi + URL dokumen.
     * GET /api/v1/admin/verifications/{mosqueId}
     */
    public function show(int $mosqueId)
    {
        $mosque = Mosque::with('verifiedByAdmin')->findOrFail($mosqueId);

        // Tambahkan URL dokumen
        $mosque->waqf_imb_document_url = $mosque->waqf_imb_document_path
            ? Storage::disk('public')->url($mosque->waqf_imb_document_path)
            : null;
        $mosque->management_decree_document_url = $mosque->management_decree_document_path
            ? Storage::disk('public')->url($mosque->management_decree_document_path)
            : null;

        // Ambil info takmir (user yang terhubung dengan masjid)
        $takmirUsers = $mosque->users()->get(['users.id', 'users.name', 'users.email']);

        return response()->json([
            'success' => true,
            'message' => 'Detail pengajuan verifikasi.',
            'data'    => [
                'mosque'  => $mosque,
                'takmirs' => $takmirUsers,
            ],
        ]);
    }

    /**
     * Approve pengajuan verifikasi.
     * POST /api/v1/admin/verifications/{mosqueId}/approve
     */
    public function approve(Request $request, int $mosqueId)
    {
        $mosque = Mosque::findOrFail($mosqueId);

        if ($mosque->verification_status !== 'submitted') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya pengajuan berstatus "submitted" yang dapat di-approve.',
            ], 422);
        }

        $validated = $request->validate([
            'verification_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $mosque->verification_status = 'verified';
        $mosque->verified_at = now();
        $mosque->verified_by = $request->user()->id;
        $mosque->verification_note = $validated['verification_note'] ?? null;
        $mosque->save();

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan verifikasi masjid berhasil di-approve.',
            'data'    => [
                'mosque' => $mosque->fresh('verifiedByAdmin'),
            ],
        ]);
    }

    /**
     * Reject pengajuan verifikasi.
     * POST /api/v1/admin/verifications/{mosqueId}/reject
     */
    public function reject(Request $request, int $mosqueId)
    {
        $mosque = Mosque::findOrFail($mosqueId);

        if ($mosque->verification_status !== 'submitted') {
            return response()->json([
                'success' => false,
                'message' => 'Hanya pengajuan berstatus "submitted" yang dapat di-reject.',
            ], 422);
        }

        $validated = $request->validate([
            'verification_note' => ['required', 'string', 'max:1000'],
        ], [
            'verification_note.required' => 'Alasan penolakan wajib diisi agar takmir tahu apa yang perlu diperbaiki.',
        ]);

        $mosque->verification_status = 'rejected';
        $mosque->verified_at = null;
        $mosque->verified_by = $request->user()->id;
        $mosque->verification_note = $validated['verification_note'];
        $mosque->save();

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan verifikasi masjid telah ditolak.',
            'data'    => [
                'mosque' => $mosque->fresh('verifiedByAdmin'),
            ],
        ]);
    }
}
