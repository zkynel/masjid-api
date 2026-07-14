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
            'type' => ['required', 'in:berita,pengumuman,kegiatan,halaman,gallery'],
            'title' => ['required', 'string', 'max:200'],
            'content' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            // Kajian
            'event_date' => ['nullable', 'date'],
            'event_time' => ['nullable'],
            'speaker' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            // Artikel
            'author' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'article_date' => ['nullable', 'date'],
            'excerpt' => ['nullable', 'string'],
            // Program
            'target_url' => ['nullable', 'url', 'max:255'],
            // Gallery
            'gallery_images' => ['nullable', 'array'],
            'gallery_images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
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

        // Upload gallery images
        $galleryPaths = [];
        if ($request->hasFile('gallery_images')) {
            foreach ($request->file('gallery_images') as $image) {
                $galleryPaths[] = $image->store('gallery', 'public');
            }
        }

        $post = Post::create([
            'mosque_id' => $mosque->id,
            'type' => $validated['type'],
            'title' => $validated['title'],
            'slug' => $postSlug,
            'content' => $validated['content'] ?? null,
            'cover_image_path' => $coverPath,
            'status' => 'draft',
            // Kajian
            'event_date' => $validated['event_date'] ?? null,
            'event_time' => $validated['event_time'] ?? null,
            'speaker' => $validated['speaker'] ?? null,
            'location' => $validated['location'] ?? null,
            // Artikel
            'author' => $validated['author'] ?? null,
            'category' => $validated['category'] ?? null,
            'article_date' => $validated['article_date'] ?? null,
            'excerpt' => $validated['excerpt'] ?? null,
            // Program
            'target_url' => $validated['target_url'] ?? null,
            // Gallery
            'gallery_images' => !empty($galleryPaths) ? $galleryPaths : null,
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
            'type' => ['nullable', 'in:berita,pengumuman,kegiatan,halaman,gallery'],
            'title' => ['nullable', 'string', 'max:200'],
            'content' => ['nullable', 'string'],
            'cover_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            // Kajian
            'event_date' => ['nullable', 'date'],
            'event_time' => ['nullable'],
            'speaker' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            // Artikel
            'author' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'article_date' => ['nullable', 'date'],
            'excerpt' => ['nullable', 'string'],
            // Program
            'target_url' => ['nullable', 'url', 'max:255'],
            // Gallery
            'gallery_images' => ['nullable', 'array'],
            'gallery_images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
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
            // Kajian
            'event_date' => array_key_exists('event_date', $validated) ? $validated['event_date'] : $post->event_date,
            'event_time' => array_key_exists('event_time', $validated) ? $validated['event_time'] : $post->event_time,
            'speaker' => array_key_exists('speaker', $validated) ? $validated['speaker'] : $post->speaker,
            'location' => array_key_exists('location', $validated) ? $validated['location'] : $post->location,
            // Artikel
            'author' => array_key_exists('author', $validated) ? $validated['author'] : $post->author,
            'category' => array_key_exists('category', $validated) ? $validated['category'] : $post->category,
            'article_date' => array_key_exists('article_date', $validated) ? $validated['article_date'] : $post->article_date,
            'excerpt' => array_key_exists('excerpt', $validated) ? $validated['excerpt'] : $post->excerpt,
            // Program
            'target_url' => array_key_exists('target_url', $validated) ? $validated['target_url'] : $post->target_url,
        ]);

        // Upload gallery images (append ke existing)
        if ($request->hasFile('gallery_images')) {
            $existingImages = $post->gallery_images ?? [];
            foreach ($request->file('gallery_images') as $image) {
                $existingImages[] = $image->store('gallery', 'public');
            }
            $post->gallery_images = $existingImages;
        }

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

    /**
     * Tambah gambar ke galeri yang sudah ada.
     * POST /api/v1/mosques/{slug}/posts/{postId}/gallery
     */
    public function addGalleryImages(Request $request, string $slug, int $postId)
    {
        $mosque = $this->getMosqueOrFail($request, $slug);
        $post = $mosque->posts()->where('id', $postId)->firstOrFail();

        $request->validate([
            'gallery_images' => ['required', 'array'],
            'gallery_images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $existingImages = $post->gallery_images ?? [];

        foreach ($request->file('gallery_images') as $image) {
            $existingImages[] = $image->store('gallery', 'public');
        }

        $post->gallery_images = $existingImages;
        $post->save();

        return response()->json([
            'success' => true,
            'message' => 'Gambar berhasil ditambahkan ke galeri.',
            'data' => [
                'post' => $post,
            ],
        ]);
    }

    /**
     * Hapus satu gambar dari galeri.
     * DELETE /api/v1/mosques/{slug}/posts/{postId}/gallery
     */
    public function removeGalleryImage(Request $request, string $slug, int $postId)
    {
        $mosque = $this->getMosqueOrFail($request, $slug);
        $post = $mosque->posts()->where('id', $postId)->firstOrFail();

        $request->validate([
            'image' => ['required', 'string'],
        ]);

        $imagePath = $request->image;
        $existingImages = $post->gallery_images ?? [];

        if (!in_array($imagePath, $existingImages)) {
            return response()->json([
                'success' => false,
                'message' => 'Gambar tidak ditemukan di galeri.',
            ], 404);
        }

        // Hapus file dari storage
        Storage::disk('public')->delete($imagePath);

        // Hapus dari array dan re-index
        $existingImages = array_values(array_filter($existingImages, fn($img) => $img !== $imagePath));

        $post->gallery_images = !empty($existingImages) ? $existingImages : null;
        $post->save();

        return response()->json([
            'success' => true,
            'message' => 'Gambar berhasil dihapus dari galeri.',
            'data' => [
                'post' => $post,
            ],
        ]);
    }
}