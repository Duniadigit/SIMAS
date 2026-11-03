<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incoming_letters', function (Blueprint $table) {
            $table->id();
            $table->string('agenda_no', 30)->unique();
            $table->string('letter_number', 100);
            $table->string('origin', 200);
            $table->string('subject', 255);
            $table->string('category', 80)->nullable()->index();
            $table->date('letter_date');
            $table->date('received_date');
            $table->string('scan_path');
            $table->string('status', 20)->default('diterima');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['status', 'received_date']);
            $table->index('received_date');
            $table->index('letter_date');
        });

        Schema::create('dispositions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incoming_letter_id')->constrained()->cascadeOnDelete();
            $table->string('assignee', 150);       // dosen / staf tujuan
            $table->text('instruction');
            $table->date('deadline')->nullable();
            $table->text('completion_note')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['incoming_letter_id', 'completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dispositions');
        Schema::dropIfExists('incoming_letters');
    }
};
