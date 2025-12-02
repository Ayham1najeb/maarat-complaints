<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'name_ar',
        'description',
        'icon',
        'color',
        'is_active',
        'order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * الشكاوي التابعة لهذا التصنيف
     */
    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }

    /**
     * عدد الشكاوي في التصنيف
     */
    public function complaintsCount(): int
    {
        return $this->complaints()->count();
    }

    /**
     * ترتيب حسب Order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * التصنيفات النشطة فقط
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
