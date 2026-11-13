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
        Schema::create('technician_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g. "GROUP 1", "GROUP 2"
            $table->string('code')->unique(); // e.g. "TEAM MTC 1", "TEAM MTC 2"
            $table->string('telegram_chat_id')->nullable(); // Chat ID Telegram (-100xxxx)
            $table->string('telegram_topic_id')->nullable(); // Topic ID / Thread ID jika menggunakan Forum Supergroup
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technician_groups');
    }
};

