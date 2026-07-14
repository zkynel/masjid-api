<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Mosque extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'custom_domain',
        'address',
        'description',
        'province',
        'city',
        'district',
        'sub_district',
        'postal',
        'contact',
        'email',
        'logo_path',
        'profile_image',
        'site_settings',
        'template_code',
        'verification_status',
        'verification_submitted_at',
        'verified_at',
        'verification_note',
        'verified_by',
        'terms_accepted_at',
    ];

    protected $casts = [
        'verification_submitted_at' => 'datetime',
        'verified_at' => 'datetime',
        'terms_accepted_at' => 'datetime',
        'site_settings' => 'array',
    ];

    protected $appends = [
        'profile_image_url',
    ];

    public function getProfileImageUrlAttribute()
    {
        return $this->profile_image
            ? Storage::disk('public')->url($this->profile_image)
            : null;
    }

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

    /**
     * Super Admin yang melakukan approve/reject.
     */
    public function verifiedByAdmin()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
