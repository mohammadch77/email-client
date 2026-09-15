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
        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')
                  ->constrained()->cascadeOnDelete();
            $table->string('filename', 255);
            $table->string('mime_type', 100);
            $table->unsignedInteger('size')->default(0); // bytes
            $table->string('imap_part_number', 50)->nullable();
            $table->string('storage_path', 500)->nullable();
            $table->boolean('is_downloaded')->default(false);
            $table->timestamps();

            $table->index('message_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attachments');
    }
};
