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
        Schema::create('email_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('email', 191);
            $table->string('display_name', 100)->nullable();
            $table->string('username', 191);
            $table->text('password'); // encrypted
            $table->string('imap_host', 255)->default('mail.iranserver.com');
            $table->unsignedSmallInteger('imap_port')->default(993);
            $table->string('imap_encryption', 10)->default('ssl');
            $table->string('smtp_host', 255)->default('mail.iranserver.com');
            $table->unsignedSmallInteger('smtp_port')->default(465);
            $table->string('smtp_encryption', 10)->default('ssl');
            $table->string('status', 20)->default('active');
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'email']);
            $table->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('email_accounts');
    }
};
