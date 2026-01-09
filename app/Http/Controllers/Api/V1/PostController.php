<?php

namespace App\Http\Controllers\Api\V1;

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

        $posts = $mosque->posts()->latest()->get();

        return response()->json([
            'success' => true,
            'data' => [
                'posts' => $posts,
            ],
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
            'title' => ['required', 'string', 'max:200'],
            'content' => ['nullable', 'string'],
        ]);

        $post = Post::create([
            'mosque_id' => $mosque->id,
            'title' => $validated['title'],
            'content' => $validated['content'] ?? null,
            'status' => 'draft',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Post berhasil dibuat (draft).',
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
            'title' => ['nullable', 'string', 'max:200'],
            'content' => ['nullable', 'string'],
        ]);

        $post->fill(array_filter($validated, fn($v) => $v !== null));
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
        $post->delete();

        return response()->json([
            'success' => true,
            'message' => 'Post berhasil dihapus.',
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
    