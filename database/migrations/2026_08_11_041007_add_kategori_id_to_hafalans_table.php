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
        Schema::table('hafalans', function (Blueprint $table) {
            $table->foreignId('kategori_id')->nullable()->after('santri_id')->constrained('hafalan_kategoris')->nullOnDelete();
            $table->index('kategori_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hafalans', function (Blueprint $table) {
            $table->dropIndex(['kategori_id']);
            $table->dropConstrainedForeignId('kategori_id');
        });
    }
};
