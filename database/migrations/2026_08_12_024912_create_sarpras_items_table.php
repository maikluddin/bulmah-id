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
        Schema::create('sarpras_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('nama');
            $table->string('kategori');
            $table->string('lokasi')->nullable();
            $table->unsignedInteger('jumlah')->default(1);
            $table->string('kondisi');
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index('team_id');
            $table->index('kategori');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sarpras_items');
    }
};
