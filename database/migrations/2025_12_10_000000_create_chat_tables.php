<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });

        Schema::create('conversation_user', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('conversation_id');
            $table->integer('user_id');

            $table->timestamp('last_read_at')->nullable();
            $table->timestamps();

            $table->unique(['conversation_id', 'user_id'], 'conversation_user_unique');

            $table->foreign('conversation_id', 'conversation_user_conversation_id_foreign')
                ->references('id')
                ->on('conversations')
                ->onDelete('cascade');

            $table->foreign('user_id', 'conversation_user_user_id_foreign')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });

        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('conversation_id');
            $table->integer('sender_id');

            $table->text('body');
            $table->timestamps();

            $table->foreign('conversation_id', 'chat_messages_conversation_id_foreign')
                ->references('id')
                ->on('conversations')
                ->onDelete('cascade');

            $table->foreign('sender_id', 'chat_messages_sender_id_foreign')
                ->references('id')
                ->on('users')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
        Schema::dropIfExists('conversation_user');
        Schema::dropIfExists('conversations');
    }
};
