<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'category_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * الشكاوي المقدمة من المستخدم
     */
    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }

    /**
     * التحديثات التي أضافها الموظف
     */
    public function complaintUpdates(): HasMany
    {
        return $this->hasMany(ComplaintUpdate::class);
    }

    /**
     * التصنيف المختص به الموظف
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * التكاليف المعينة للموظف
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'assigned_to');
    }

    /**
     * التكاليف التي قام بتعيينها
     */
    public function assignedTasks(): HasMany
    {
        return $this->hasMany(Assignment::class, 'assigned_by');
    }

    /**
     * هل المستخدم مدير؟
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * هل المستخدم موظف؟
     */
    public function isEmployee(): bool
    {
        return $this->role === 'employee';
    }

    /**
     * هل المستخدم مواطن؟
     */
    public function isCitizen(): bool
    {
        return $this->role === 'citizen';
    }

    /**
     * Scopes
     */
    public function scopeAdmins($query)
    {
        return $query->where('role', 'admin');
    }

    public function scopeEmployees($query)
    {
        return $query->where('role', 'employee');
    }

    public function scopeCitizens($query)
    {
        return $query->where('role', 'citizen');
    }
}
