<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stacking_frameworks', function (Blueprint $table): void {
            $table->string('approval_reference')->nullable();
            $table->date('approval_date')->nullable();
            $table->date('effective_date')->nullable();
            $table->date('expiration_date')->nullable();
            $table->unsignedInteger('version_number')->default(1);
            $table->foreignId('previous_version_id')->nullable()
                ->constrained('stacking_frameworks')->nullOnDelete();
        });

        Schema::table('academic_credit_recognitions', function (Blueprint $table): void {
            $table->json('framework_snapshot')->nullable();
            $table->text('review_notes')->nullable();
            $table->text('student_response')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('report_generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('report_generated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('academic_credit_recognitions', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('report_generated_by');
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn([
                'framework_snapshot',
                'review_notes',
                'student_response',
                'reviewed_at',
                'report_generated_at',
            ]);
        });

        Schema::table('stacking_frameworks', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('previous_version_id');
            $table->dropColumn([
                'approval_reference',
                'approval_date',
                'effective_date',
                'expiration_date',
                'version_number',
            ]);
        });
    }
};
