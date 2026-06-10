<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Mosque;
use App\Models\Post;

class PostController extends Controller
{
    private function getMosqueOrFail(Request $request, string $slug): Mosque
    {
        $mosque = Mosque::where('slug', $slug)->firstOrFail();

        $owns = $request->user()
            ->mosques()
            ->where('mosques.id', $mosque->id)
            ->exists();

        if (!$owns) {
            abort(403, 'Anda tidak memiliki akses ke masjid ini.');
        }

        return $mosque;
    }

        public function index(Request $request, string $slug)
    {
        $mosque = $this->getMosqueOrFail($request, $slug);

        $posts = $mosque->posts()
            ->when($request->type, function ($query, $type) {
                $query->where('type', $type);
            })
            ->latest()
            ->paginate($request->integer('per_page', 10));

        return response()->json([
            'success' => true,
            'message' => 'Daftar post berhasil diambil.',
            'data' => $posts,
        ]);
    }

    public function show(Request $request, string $slug, int $postId)
    {
        $mosque = $this->getMosqueOrFail($request, $slug);

        $post = $mosque->posts()->where('id', $postId)->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'post' => $post,
            ],
        ]);
    }

        public function store(Request $request, string $slug)
    {
        $mosque = $this->getMosqueOrFail($request, $slug);

        $validated = $request->validate([
            'type' => ['required', 'in:berita,pengumuman,kegiatan,halaman'],
            'title' => ['required', 'string', 'max:200'],
            'content' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $postSlug = Str::slug($validated['title']);

        $exists = $mosque->posts()
            ->where('slug', $postSlug)
            ->exists();

        if ($exists) {
            $postSlug = $postSlug . '-' . time();
        }

        $coverPath = null;

        if ($request->hasFile('cover_image')) {
            $coverPath = $request->file('cover_image')->store('posts/covers', 'public');
        }

        $post = Post::create([
            'mosque_id' => $mosque->id,
            'type' => $validated['type'],
            'title' => $validated['title'],
            'slug' => $postSlug,
            'content' => $validated['content'] ?? null,
            'cover_image_path' => $coverPath,
            'status' => 'draft',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Post berhasil dibuat.',
            'data' => [
                'post' => $post,
            ],
        ], 201);
    }

        public function update(Request $request, string $slug, int $postId)
    {
        $mosque = $this->getMosqueOrFail($request, $slug);

        $post = $mosque->posts()->where('id', $postId)->firstOrFail();

        $validated = $request->validate([
            'type' => ['nullable', 'in:berita,pengumuman,kegiatan,halaman'],
            'title' => ['nullable', 'string', 'max:200'],
            'content' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        if (isset($validated['title']) && $validated['title'] !== $post->title) {
            $newSlug = Str::slug($validated['title']);

            $exists = $mosque->posts()
                ->where('slug', $newSlug)
                ->where('id', '!=', $post->id)
                ->exists();

            if ($exists) {
                $newSlug = $newSlug . '-' . time();
            }

            $post->slug = $newSlug;
        }

        if ($request->hasFile('cover_image')) {
            if ($post->cover_image_path) {
                Storage::disk('public')->delete($post->cover_image_path);
            }

            $post->cover_image_path = $request->file('cover_image')->store('posts/covers', 'public');
        }

        $post->fill([
            'type' => $validated['type'] ?? $post->type,
            'title' => $validated['title'] ?? $post->title,
            'content' => array_key_exists('content', $validated) ? $validated['content'] : $post->content,
        ]);

        $post->save();

        return response()->json([
            'success' => true,
            'message' => 'Post berhasil diperbarui.',
            'data' => [
                'post' => $post,
            ],
        ]);
    }

        public function destroy(Request $request, string $slug, int $postId)
    {
        $mosque = $this->getMosqueOrFail($request, $slug);

        $post = $mosque->posts()->where('id', $postId)->firstOrFail();

        if ($post->cover_image_path) {
            Storage::disk('public')->delete($post->cover_image_path);
        }

        $post->delete();

        return response()->json([
            'success' => true,
            'message' => 'Post berhasil dihapus.',
            'data' => null,
        ]);
    }

    public function publish(Request $request, string $slug, int $postId)
    {
        $mosque = $this->getMosqueOrFail($request, $slug);

        $post = $mosque->posts()->where('id', $postId)->firstOrFail();

        $post->status = 'published';
        $post->published_at = now();
        $post->save();

        return response()->json([
            'success' => true,
            'message' => 'Post berhasil dipublish.',
            'data' => [
                'post' => $post,
            ],
        ]);
    }

    public function unpublish(Request $request, string $slug, int $postId)
    {
        $mosque = $this->getMosqueOrFail($request, $slug);

        $post = $mosque->posts()->where('id', $postId)->firstOrFail();

        $post->status = 'draft';
        $post->published_at = null;
        $post->save();

        return response()->json([
            'success' => true,
            'message' => 'Post berhasil di-unpublish (kembali draft).',
            'data' => [
                'post' => $post,
            ],
        ]);
    }
}
    