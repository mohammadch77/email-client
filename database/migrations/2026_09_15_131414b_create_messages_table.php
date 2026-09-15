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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('email_account_id')
                  ->constrained()->cascadeOnDelete();
            $table->foreignId('folder_id')
                  ->constrained()->cascadeOnDelete();
            $table->foreignId('thread_id')
                  ->nullable()->constrained()->nullOnDelete();
            $table->string('imap_uid', 20)->nullable();
            $table->string('message_id_header', 500)->nullable();
            $table->string('in_reply_to', 500)->nullable();
            $table->text('references')->nullable();
            $table->string('from_email', 191);
            $table->string('from_name', 100)->nullable();
            $table->string('subject', 500)->nullable();
            $table->mediumText('body_text')->nullable();
            $table->mediumText('body_html')->nullable();
            $table->string('status', 20)->default('received');
            // received|sending|sent|draft|failed
            $table->boolean('is_read')->default(false);
            $table->boolean('is_starred')->default(false);
            $table->boolean('has_attachments')->default(false);
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamps();

            $table->unique(['email_account_id', 'folder_id', 'imap_uid']);
            $table->index(['folder_id', 'received_at']);
            $table->index('thread_id');
            $table->index('message_id_header');
            $table->fullText(['subject', 'body_text', 'from_email']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
