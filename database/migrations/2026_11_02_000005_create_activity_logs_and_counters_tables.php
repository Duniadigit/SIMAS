<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->morphs('subject'); // subject_type (outgoing|incoming), subject_id + index
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('actor_name', 150);
            $table->string('action', 50);
            $table->text('description');
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
        });

        // Penghitung nomor surat per tahun; dikunci (lockForUpdate) saat penomoran
        // supaya dua ACC bersamaan tidak pernah mendapat nomor yang sama.
        Schema::create('letter_counters', function (Blueprint $table) {
            $table->unsignedSmallInteger('year')->primary();
            $table->unsignedInteger('last_number')->default(0);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('letter_counters');
        Schema::dropIfExists('activity_logs');
    }
};
