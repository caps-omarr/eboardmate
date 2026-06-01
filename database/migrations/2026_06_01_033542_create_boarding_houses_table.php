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
        Schema::create('boarding_houses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('owner_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('name');
            $table->string('slug')->unique();

            $table->text('description')->nullable();
            $table->text('location_description')->nullable();
            $table->string('address')->nullable();

            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            $table->decimal('rent_price', 10, 2)->default(0);

            $table->unsignedInteger('total_rooms')->default(0);
            $table->unsignedInteger('available_rooms')->default(0);
            $table->unsignedInteger('total_bedspaces')->default(0);
            $table->unsignedInteger('available_bedspaces')->default(0);

            $table->json('amenities')->nullable();
            $table->text('rules')->nullable();

            $table->string('status')->default('pending')->index();
            $table->boolean('is_verified')->default(false)->index();

            $table->timestamp('verified_at')->nullable();

            $table->foreignId('verified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->text('rejection_reason')->nullable();
            $table->text('deactivated_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('owner_id');
            $table->index(['status', 'is_verified']);
            $table->index(['latitude', 'longitude']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('boarding_houses');
    }
};