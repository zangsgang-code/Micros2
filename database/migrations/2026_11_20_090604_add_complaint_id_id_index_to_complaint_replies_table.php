<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('complaint_replies', function (Blueprint $table) {
            $table->index(['complaint_id', 'id'], 'complaint_replies_complaint_id_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('complaint_replies', function (Blueprint $table) {
            $table->dropIndex('complaint_replies_complaint_id_id_index');
        });
    }
};
