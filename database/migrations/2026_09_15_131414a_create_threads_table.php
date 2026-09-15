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
        Schema::create('threads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_account_id')
                  ->constrained()->cascadeOnDelete();
            $table->string('subject', 500)->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->unsignedTinyInteger('message_count')->default(1);
            $table->boolean('has_unread')->default(true);
            $table->boolean('is_starred')->default(false);
            $table->timestamps();

            $table->index(['email_account_id', 'last_message_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('threads');
    }
};
