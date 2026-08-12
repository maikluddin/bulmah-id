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
        Schema::create('hafalans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('santri_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ustad_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type')->default('hafalan_baru');
            $table->unsignedTinyInteger('juz')->nullable();
            $table->string('surat');
            $table->unsignedInteger('ayat_from')->nullable();
            $table->unsignedInteger('ayat_to')->nullable();
            $table->date('tanggal');
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index(['santri_id', 'tanggal']);
            $table->index('team_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hafalans');
    }
};
