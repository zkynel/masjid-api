<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Mosque;

class PublicController extends Controller
{
    public function postsBySlug(string $slug)
    {
        $mosque = Mosque::where('slug', $slug)->firstOrFail();

        $posts = $mosque->posts()
            ->where('status', 'published')
            ->latest()
            ->get();

        return response()->json([
            'success' => true,
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
                    'contact' => $mosque->contact,
                    'logo_path' => $mosque->logo_path,
                    'template_code' => $mosque->template_code,
                ],
            ],
        ]);
    }
}
