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
        Schema::create('perbaikan_sarpras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sarpras_item_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('pelapor_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('deskripsi');
            $table->string('status');
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index('team_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('perbaikan_sarpras');
    }
};
