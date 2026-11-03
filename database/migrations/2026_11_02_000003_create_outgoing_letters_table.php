<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outgoing_letters', function (Blueprint $table) {
            $table->id();
            $table->string('registration_no', 30)->unique();
            $table->foreignId('letter_type_id')->constrained()->restrictOnDelete();

            // Data pemohon (mahasiswa/dosen tanpa login)
            $table->string('requester_name', 150);
            $table->string('requester_identifier', 30)->index(); // NPM / NIDN
            $table->string('requester_type', 20);                // mahasiswa | dosen
            $table->string('email', 150)->nullable();
            $table->string('phone', 30)->nullable();

            $table->string('subject', 255);
            $table->text('details')->nullable();

            $table->string('status', 30)->default('diajukan');
            $table->string('letter_number', 100)->nullable()->unique();
            $table->date('letter_date')->nullable();
            $table->string('draft_path')->nullable();
            $table->string('final_path')->nullable();
            $table->text('review_note')->nullable(); // catatan penolakan / revisi

            $table->foreignId('processed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outgoing_letters');
    }
};
