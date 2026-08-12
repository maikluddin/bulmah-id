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
        Schema::table('santris', function (Blueprint $table) {
            $table->string('address_province')->nullable()->after('address');
            $table->string('address_city')->nullable()->after('address_province');
            $table->string('address_district')->nullable()->after('address_city');
            $table->string('address_village')->nullable()->after('address_district');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('santris', function (Blueprint $table) {
            $table->dropColumn(['address_province', 'address_city', 'address_district', 'address_village']);
        });
    }
};
