<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_direct_chats', function (Blueprint $table) {
            $table->id();
            $table->string('pair_key', 64);
            $table->foreignId('user_low_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('user_high_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('last_message_at')->nullable()->index();
            $table->string('last_message_preview', 255)->nullable();
            $table->timestamp('low_last_read_at')->nullable();
            $table->timestamp('high_last_read_at')->nullable();
            $table->timestamps();

            $table->unique('pair_key');
            $table->index(['user_low_id', 'last_message_at']);
            $table->index(['user_high_id', 'last_message_at']);
        });

        Schema::create('app_direct_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('chat_id')->constrained('app_direct_chats')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('body');
            $table->timestamps();

            $table->index(['chat_id', 'id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_direct_chat_messages');
        Schema::dropIfExists('app_direct_chats');
    }
};
