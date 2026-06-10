<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Mosque;

class PublicController extends Controller
{
        public function postsBySlug(Request $request, string $slug)
    {
        $mosque = Mosque::where('slug', $slug)->firstOrFail();

        $posts = $mosque->posts()
            ->where('status', 'published')
            ->when($request->type, function ($query, $type) {
                $query->where('type', $type);
            })
            ->latest()
            ->paginate($request->integer('per_page', 10));

        return response()->json([
            'success' => true,
            'message' => 'Daftar post berhasil diambil.',
            'data' => [
                'mosque' => [
                    'name' => $mosque->name,
                    'slug' => $mosque->slug,
                    'template_code' => $mosque->template_code,
                ],
                'posts' => $posts,
            ],
        ]);
    }

        public function postDetailBySlug(string $slug, string $postSlug)
    {
        $mosque = Mosque::where('slug', $slug)->firstOrFail();

        $post = $mosque->posts()
            ->where('slug', $postSlug)
            ->where('status', 'published')
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'message' => 'Detail post berhasil diambil.',
            'data' => [
                'mosque' => [
                    'name' => $mosque->name,
                    'slug' => $mosque->slug,
                    'template_code' => $mosque->template_code,
                ],
                'post' => $post,
            ],
        ]);
    }

        public function profileBySlug(string $slug)
    {
        $mosque = Mosque::where('slug', $slug)->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'mosque' => [
                    'name' => $mosque->name,
                    'slug' => $mosque->slug,
                    'address' => $mosque->address,
                    'description' => $mosque->description,

                    'province' => $mosque->province,
                    'city' => $mosque->city,
                    'district' => $mosque->district,
                    'sub_district' => $mosque->sub_district,
                    'postal' => $mosque->postal,

                    'contact' => $mosque->contact,
                    'email' => $mosque->email,
                    'logo_path' => $mosque->logo_path,
                    'template_code' => $mosque->template_code,
                ],
            ],
        ]);
    }
}
