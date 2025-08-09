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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // 如果有用戶系統，關聯 users 表
            $table->string('name');
            $table->string('file_path'); // 儲存在 Storage 的路徑
            $table->text('summary')->nullable(); // AI 生成的摘要
            $table->string('status')->default('uploaded'); // uploaded, pending_ai, processed_ai, ai_failed, ai_connection_error, ai_process_error
            $table->string('category')->nullable(); // 文件分類，例如 "Contract", "Report", "FAQ"
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
