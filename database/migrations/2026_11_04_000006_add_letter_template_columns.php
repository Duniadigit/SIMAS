<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('letter_types', function (Blueprint $table) {
            $table->string('default_title', 150)->nullable()->after('code'); // judul surat keterangan, mis. SURAT KETERANGAN AKTIF KULIAH
            $table->text('body_template')->nullable()->after('default_title');
        });

        Schema::table('outgoing_letters', function (Blueprint $table) {
            // Isian template surat (judul, lampiran, tujuan, tempat, redaksi). NULL = draf berasal dari unggahan PDF.
            $table->json('letter_data')->nullable()->after('review_note');
        });
    }

    public function down(): void
    {
        Schema::table('outgoing_letters', fn (Blueprint $t) => $t->dropColumn('letter_data'));
        Schema::table('letter_types', fn (Blueprint $t) => $t->dropColumn(['default_title', 'body_template']));
    }
};
