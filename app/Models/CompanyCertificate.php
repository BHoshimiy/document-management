<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class CompanyCertificate extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['company_id', 'certificate', 'date_from', 'date_to'];

    protected function casts(): array
    {
        return [
            'date_from' => 'date',
            'date_to' => 'date',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function getIsExpiredAttribute(): bool
    {
        return $this->date_to !== null && $this->date_to->isPast();
    }

    public function getFileUrlAttribute(): ?string
    {
        return $this->certificate ? Storage::disk('public')->url($this->certificate) : null;
    }

    public function scopeValidOn($query, Carbon|string $date)
    {
        return $query->whereDate('date_from', '<=', $date)
            ->where(fn ($q) => $q->whereNull('date_to')->orWhereDate('date_to', '>=', $date));
    }

    public function scopeExpiringWithin($query, int $days)
    {
        return $query->whereNotNull('date_to')
            ->whereBetween('date_to', [now()->toDateString(), now()->addDays($days)->toDateString()]);
    }
}
