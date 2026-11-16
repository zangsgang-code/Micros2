<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Persistent student request records, separate from `user_stacking_progress`.
 * Framework completion never creates a request automatically. Legacy status
 * fields remain for historical records; the current workflow records only
 * internal UPSKILL review and report-generation statuses.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('academic_credit_recognitions')) {
            Schema::create('academic_credit_recognitions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('stacking_framework_id')->constrained('stacking_frameworks')->cascadeOnDelete();
                // Legacy records retain their original status; new requests use internal statuses.
                $table->string('status')->default('pending');
                $table->foreignId('unit_reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('unit_reviewed_at')->nullable();
                $table->text('unit_recommendation')->nullable();
                $table->foreignId('dean_endorsed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('dean_endorsed_at')->nullable();
                $table->foreignId('registrar_recorded_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('registrar_recorded_at')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'stacking_framework_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('academic_credit_recognitions');
    }
};
