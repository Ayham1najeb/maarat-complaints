<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplaintUpdate extends Model
{
    use HasFactory;

    protected $fillable = [
        'complaint_id',
        'user_id',
        'old_status',
        'new_status',
        'comment',
        'is_public',
        'read_at',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    /**
     * الشكوى
     */
    public function complaint(): BelongsTo
    {
        return $this->belongsTo(Complaint::class);
    }

    /**
     * المستخدم (الموظف الذي أضاف التحديث)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * التحديثات العامة فقط (للمواطنين)
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * التحديثات الخاصة (للإدارة فقط)
     */
    public function scopePrivate($query)
    {
        return $query->where('is_public', false);
    }
}
