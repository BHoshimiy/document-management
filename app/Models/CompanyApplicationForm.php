<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyApplicationForm extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUSES = ['draft', 'submitted', 'approved', 'rejected'];

    protected $fillable = ['company_id', 'number', 'status', 'payload', 'submitted_at'];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'submitted_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function markSubmitted(): void
    {
        $this->forceFill([
            'status' => 'submitted',
            'submitted_at' => now(),
        ])->save();
    }
}
