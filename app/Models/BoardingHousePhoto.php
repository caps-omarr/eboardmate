<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BoardingHousePhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'boarding_house_id',
        'file_path',
        'original_name',
        'mime_type',
        'file_size',
        'alt_text',
        'is_primary',
        'sort_order',
    ];

    protected $appends = [
        'url',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'is_primary' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function boardingHouse(): BelongsTo
    {
        return $this->belongsTo(BoardingHouse::class);
    }

    public function getUrlAttribute(): string
    {
        if (! $this->file_path) {
            return '';
        }

        $version = $this->updated_at ? $this->updated_at->timestamp : time();

        if (str_starts_with($this->file_path, 'http://') || str_starts_with($this->file_path, 'https://')) {
            return $this->file_path . (str_contains($this->file_path, '?') ? '&v=' : '?v=') . $version;
        }

        $defaultDisk = config('filesystems.default', 'public');

        if ($defaultDisk === 'cloudinary') {
            $url = \Illuminate\Support\Facades\Storage::disk('cloudinary')->url($this->file_path);
            return $url . (str_contains($url, '?') ? '&v=' : '?v=') . $version;
        }

        $cleanPath = ltrim(str_replace('storage/', '', $this->file_path), '/');
        return asset('storage/' . $cleanPath) . '?v=' . $version;
    }
}