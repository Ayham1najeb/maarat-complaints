<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Area extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * الشكاوي التابعة لهذه المنطقة
     */
    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }

    /**
     * عدد الشكاوي في المنطقة
     */
    public function complaintsCount(): int
    {
        return $this->complaints()->count();
    }

    /**
     * الشكاوي المعلقة في المنطقة
     */
    public function pendingComplaints(): HasMany
    {
        return $this->complaints()->where('status', 'pending');
    }
}
