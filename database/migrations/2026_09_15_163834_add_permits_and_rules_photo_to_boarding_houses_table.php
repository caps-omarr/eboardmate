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
            $table->string('business_permit_url')->nullable()->after('rules');
            $table->boolean('is_permit_processing')->default(false)->after('business_permit_url');
            $table->text('permit_processing_notes')->nullable()->after('is_permit_processing');
            $table->string('house_rules_image_url')->nullable()->after('permit_processing_notes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('boarding_houses', function (Blueprint $table) {
            $table->dropColumn([
                'business_permit_url',
                'is_permit_processing',
                'permit_processing_notes',
                'house_rules_image_url',
            ]);
        });
    }
};
