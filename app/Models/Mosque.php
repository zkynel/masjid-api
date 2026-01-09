<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Mosque extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'custom_domain',
        'address',
        'description',
        'contact',
        'logo_path',
        'template_code',
        'verification_status',
        'verification_submitted_at',
        'verified_at',
        'verification_note',
        'terms_accepted_at',
    ];

    protected $casts = [
        'verification_submitted_at' => 'datetime',
        'verified_at' => 'datetime',
        'terms_accepted_at' => 'datetime',
    ];

    public function users()
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role_in_mosque')
            ->withTimestamps();
    }

    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}
