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
        Schema::table('hafalan_kategoris', function (Blueprint $table) {
            $table->dropUnique('hafalan_kategoris_team_id_kelas_id_nama_unique');
            $table->dropIndex(['kelas_id']);
            $table->dropConstrainedForeignId('kelas_id');
        });

        Schema::table('hafalan_kategoris', function (Blueprint $table) {
            $table->string('jenis')->default('alquran')->after('team_id');
        });

        Schema::table('hafalan_kategoris', function (Blueprint $table) {
            $table->unique(['team_id', 'jenis', 'nama']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hafalan_kategoris', function (Blueprint $table) {
            $table->dropUnique(['team_id', 'jenis', 'nama']);
            $table->dropColumn('jenis');
        });

        Schema::table('hafalan_kategoris', function (Blueprint $table) {
            $table->foreignId('kelas_id')->after('team_id')->constrained()->cascadeOnDelete();
            $table->index('kelas_id');
            $table->unique(['team_id', 'kelas_id', 'nama']);
        });
    }
};
