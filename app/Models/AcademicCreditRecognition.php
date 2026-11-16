<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A student's internal UPSKILL request for an evidence report.
 *
 * Legacy institutional-decision columns remain in the database for historical
 * records and are not written by the current request/review workflow.
 */
class AcademicCreditRecognition extends Model
{
    protected $fillable = [
        'user_id',
        'stacking_framework_id',
        'status',
        'notes',
        'framework_snapshot',
        'review_notes',
        'student_response',
        'reviewed_by',
        'reviewed_at',
        'report_generated_by',
        'report_generated_at',
    ];

    protected function casts(): array
    {
        return [
            'framework_snapshot' => 'array',
            'reviewed_at' => 'datetime',
            'report_generated_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function framework(): BelongsTo
    {
        return $this->belongsTo(StackingFramework::class, 'stacking_framework_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function reportGenerator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'report_generated_by');
    }
}
