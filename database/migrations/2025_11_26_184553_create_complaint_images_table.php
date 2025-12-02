<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaint_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complaint_id')->constrained()->cascadeOnDelete();
            $table->string('image_path');
            $table->string('thumbnail_path')->nullable(); // صورة مصغرة (للعرض السريع)
            $table->unsignedInteger('file_size')->nullable(); // حجم الملف بالـ bytes
            $table->string('mime_type')->nullable(); // نوع الملف
            $table->integer('order')->default(0); // ترتيب الصور
            $table->timestamps();

            $table->index('complaint_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_images');
    }
};
