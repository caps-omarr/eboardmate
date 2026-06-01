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
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();

            $table->foreignId('boarding_house_id')
                ->constrained('boarding_houses')
                ->cascadeOnDelete();

            $table->string('reference_code')->unique();

            $table->string('guest_name')->index();
            $table->string('guest_email')->index();
            $table->string('guest_phone')->index();

            $table->date('preferred_move_in_date');
            $table->text('message')->nullable();

            $table->string('status')->default('pending')->index();

            $table->text('owner_response')->nullable();

            $table->timestamp('responded_at')->nullable();

            $table->foreignId('responded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('expired_at')->nullable();

            $table->timestamp('approved_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamp('email_notification_sent_at')->nullable();
            $table->string('email_notification_status')->nullable()->index();
            $table->text('email_notification_error')->nullable();

            $table->string('submission_ip', 45)->nullable()->index();
            $table->text('user_agent')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('boarding_house_id');
            $table->index(['boarding_house_id', 'status']);
            $table->index(['reference_code', 'guest_email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};