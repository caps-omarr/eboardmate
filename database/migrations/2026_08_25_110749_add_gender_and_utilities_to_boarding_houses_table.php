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
        Schema::table('boarding_houses', function (Blueprint $table) {
            $table->string('allowed_genders')->nullable()->after('rules');
            $table->boolean('includes_water')->default(false)->after('allowed_genders');
            $table->boolean('includes_electricity')->default(false)->after('includes_water');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('boarding_houses', function (Blueprint $table) {
            $table->dropColumn(['allowed_genders', 'includes_water', 'includes_electricity']);
        });
    }
};
