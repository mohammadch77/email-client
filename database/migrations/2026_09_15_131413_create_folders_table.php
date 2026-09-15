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
        Schema::create('folders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_account_id')
                  ->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('type', 20);
            // inbox|sent|drafts|trash|spam|archive|custom
            $table->string('imap_name', 255);
            // actual IMAP folder name e.g. "INBOX"
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->unsignedInteger('unread_count')->default(0);
            $table->unsignedInteger('total_count')->default(0);
            $table->timestamps();

            $table->unique(['email_account_id', 'imap_name']);
            $table->index('email_account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('folders');
    }
};
