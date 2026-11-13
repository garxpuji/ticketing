<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TechnicianGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'telegram_chat_id',
        'telegram_topic_id',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Technicians belonging to this group
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Active technician members
     */
    public function technicians(): HasMany
    {
        return $this->hasMany(User::class)
            ->where('role', UserRole::Technician->value)
            ->where('is_active', true);
    }

    /**
     * Tickets assigned to this group
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    /**
     * Check if group has Telegram target configured
     */
    public function hasTelegramConfig(): bool
    {
        return !empty($this->telegram_chat_id);
    }
}

