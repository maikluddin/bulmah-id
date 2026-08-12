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
        Schema::table('teams', function (Blueprint $table) {
            $table->foreignId('yayasan_id')->nullable()->after('id')->constrained('yayasans')->nullOnDelete();
            $table->string('type')->nullable()->after('is_personal');
            $table->text('description')->nullable()->after('type');
            $table->string('address')->nullable()->after('description');
            $table->string('phone')->nullable()->after('address');
            $table->string('email')->nullable()->after('phone');
            $table->string('photo_path')->nullable()->after('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('teams', function (Blueprint $table) {
            $table->dropConstrainedForeignId('yayasan_id');
            $table->dropColumn(['type', 'description', 'address', 'phone', 'email', 'photo_path']);
        });
    }
};
