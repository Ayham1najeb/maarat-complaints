<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('name_ar'); // الاسم بالعربي
            $table->text('description')->nullable();
            $table->string('icon')->default('📋'); // أيقونة emoji
            $table->string('color')->default('#3B82F6'); // لون hex
            $table->boolean('is_active')->default(true);
            $table->integer('order')->default(0); // ترتيب العرض
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
