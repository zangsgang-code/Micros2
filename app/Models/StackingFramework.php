<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * An institutionally approved arrangement under which multiple
 * microcredentials (courses) contribute to a single larger recognition.
 *
 * This is NOT the same thing as a `Pathway`. `Pathway` remains a
 * career/learning recommendation feature; `StackingFramework` is the
 * authoritative academic stacking structure.
 */
class StackingFramework extends Model
{
    protected $fillable = [
        'name',
        'description',
        'status',
        'approving_academic_unit',
        'approval_reference',
        'approval_date',
        'effective_date',
        'expiration_date',
        'version_number',
        'previous_version_id',
        'approved_by',
        'approved_at',
        'target_recognition',
        'recognition_target_type',
        'completion_mode',
        'required_count',
        'cumulative_outcomes',
        'equivalent_course',
        'equivalent_units',
        'sequence_required',
        'credit_recognition_conditions',
        'pqf_level',
        'credit_equivalency',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'cumulative_outcomes' => 'array',
            'sequence_required' => 'boolean',
            'is_active' => 'boolean',
            'approved_at' => 'datetime',
            'approval_date' => 'date',
            'effective_date' => 'date',
            'expiration_date' => 'date',
            'version_number' => 'integer',
            'completion_mode' => 'string',
            'required_count' => 'integer',
        ];
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function previousVersion(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_version_id');
    }

    public function newerVersions(): HasMany
    {
        return $this->hasMany(self::class, 'previous_version_id');
    }

    public function isAvailableToStudents(): bool
    {
        $today = now()->toDateString();

        return $this->status === 'approved'
            && $this->is_active
            && (! $this->effective_date || $this->effective_date->toDateString() <= $today)
            && (! $this->expiration_date || $this->expiration_date->toDateString() >= $today);
    }

    public function requirements(): HasMany
    {
        return $this->hasMany(StackingFrameworkRequirement::class)->orderBy('order');
    }

    public function courses()
    {
        return $this->requirements()->with('course');
    }

    public function userProgress(): HasMany
    {
        return $this->hasMany(UserStackingProgress::class);
    }

    public function academicCreditRecognitions(): HasMany
    {
        return $this->hasMany(AcademicCreditRecognition::class);
    }
}
