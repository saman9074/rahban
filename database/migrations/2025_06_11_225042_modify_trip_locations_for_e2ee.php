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
        Schema::table('trip_locations', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude']);
            $table->text('encrypted_data')->after('trip_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trip_locations', function (Blueprint $table) {
            $table->dropColumn('encrypted_data');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
        });
    }
};
