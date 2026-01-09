<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'mosque_id',
        'title',
        'slug',
        'content',
        'cover_image_path',
        'status',
        'published_at',
    ];

    protected $casts = [
        'published_at' => 'datetime',
    ];

    public function mosque()
    {
        return $this->belongsTo(Mosque::class);
    }
}
