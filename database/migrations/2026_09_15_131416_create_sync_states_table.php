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
        Schema::create('sync_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_account_id')
                  ->constrained()->cascadeOnDelete();
            $table->foreignId('folder_id')
                  ->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('last_uid')->nullable();
            $table->unsignedInteger('uid_validity')->nullable();
            $table->boolean('full_sync_done')->default(false);
            $table->timestamp('synced_at')->nullable();
            $table->string('status', 20)->default('idle');
            // idle|running|failed
            $table->timestamps();

            $table->unique(['email_account_id', 'folder_id']);
            $table->index('email_account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_states');
    }
};
