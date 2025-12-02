<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Complaint extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'citizen_name',
        'citizen_phone',
        'citizen_email',
        'category_id',
        'area_id',
        'title',
        'description',
        'location_address',
        'status',
        'priority',
        'tracking_number',
        'admin_notes',
        'reviewed_at',
        'resolved_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        // توليد رقم تتبع تلقائي فريد
        static::creating(function ($complaint) {
            if (empty($complaint->tracking_number)) {
                // توليد رقم تتبع فريد مع التحقق من عدم التكرار
                do {
                    // استخدام timestamp + random للتأكد من الفرادة
                    $timestamp = now()->format('ymdHis'); // مثال: 241202103045
                    $random = strtoupper(Str::random(6)); // 6 أحرف عشوائية
                    $trackingNumber = 'CM' . $timestamp . $random;
                    
                    // التحقق من عدم وجود هذا الرقم مسبقاً
                    $exists = self::where('tracking_number', $trackingNumber)->exists();
                } while ($exists); // إعادة التوليد إذا كان موجوداً (احتمال ضئيل جداً)
                
                $complaint->tracking_number = $trackingNumber;
            }
        });
    }

    /**
     * المستخدم صاحب الشكوى (إذا كان مسجل)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * التصنيف
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * المنطقة
     */
    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    /**
     * الصور المرفقة
     */
    public function images(): HasMany
    {
        return $this->hasMany(ComplaintImage::class)->orderBy('order');
    }

    /**
     * التحديثات
     */
    public function updates(): HasMany
    {
        return $this->hasMany(ComplaintUpdate::class)->latest();
    }

    /**
     * التعيين الحالي
     */
    public function assignment(): HasOne
    {
        return $this->hasOne(Assignment::class)->latest();
    }

    /**
     * كل التعيينات
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }

    /**
     * Scopes للفلترة
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    public function scopeByArea($query, $areaId)
    {
        return $query->where('area_id', $areaId);
    }

    /**
     * حساب مدة الحل
     */
    public function getResolutionTimeAttribute(): ?int
    {
        if ($this->resolved_at) {
            return $this->created_at->diffInHours($this->resolved_at);
        }
        return null;
    }

    /**
     * هل الشكوى معلقة؟
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * هل الشكوى محلولة؟
     */
    public function isResolved(): bool
    {
        return $this->status === 'resolved';
    }
}
