<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ComplaintImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'complaint_id',
        'image_path',
        'thumbnail_path',
        'file_size',
        'mime_type',
        'order',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'order' => 'integer',
    ];

    /**
     * الشكوى التابعة لها الصورة
     */
    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    /**
     * رابط الصورة الكامل
     */
    public function getUrlAttribute(): string
    {
        return Storage::url($this->image_path);
    }

    /**
     * رابط الصورة المصغرة
     */
    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->thumbnail_path ? Storage::url($this->thumbnail_path) : null;
    }

    /**
     * حذف الصورة من التخزين عند حذف السجل
     */
    protected static function boot()
    {
        parent::boot();

        static::deleting(function ($image) {
            if (Storage::exists($image->image_path)) {
                Storage::delete($image->image_path);
            }
            if ($image->thumbnail_path && Storage::exists($image->thumbnail_path)) {
                Storage::delete($image->thumbnail_path);
            }
        });
    }
}
