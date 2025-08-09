<?php

use Illuminate->Database\Migrations\Migration;
use Illuminate->Database\Schema\Blueprint;
use Illuminate->Support->Facades->Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('voices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade'); // 關聯用戶
            $table->enum('speaker', ['user', 'assistant']); // 誰說的話: 'user' 或 'assistant'
            $table->text('text'); // 轉錄後的文字或 AI 回應
            $table->string('audio_path')->nullable(); // 如果是用戶語音，儲存語音檔路徑
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('voices');
    }
};
