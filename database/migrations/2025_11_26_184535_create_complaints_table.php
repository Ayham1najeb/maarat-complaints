<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();

            // معلومات المواطن (يمكن يكون مسجل أو ضيف)
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('citizen_name');
            $table->string('citizen_phone');
            $table->string('citizen_email')->nullable();

            // تفاصيل الشكوى
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('area_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title');
            $table->text('description');

            // الموقع
            $table->text('location_address'); // العنوان النصي


            // الحالة والأولوية
            $table->enum('status', [
                'pending',      // جديدة
                'in_review',    // قيد المراجعة
                'in_progress',  // قيد المعالجة
                'resolved',     // تم الحل
                'rejected'      // مرفوضة
            ])->default('pending');

            $table->enum('priority', [
                'low',      // منخفضة
                'medium',   // متوسطة
                'high',     // عالية
                'urgent'    // عاجلة
            ])->default('medium');

            // رقم التتبع (فريد)
            $table->string('tracking_number', 20)->unique();

            // ملاحظات إدارية (خاصة بالموظفين)
            $table->text('admin_notes')->nullable();

            // تواريخ مهمة
            $table->timestamp('reviewed_at')->nullable(); // تاريخ المراجعة
            $table->timestamp('resolved_at')->nullable(); // تاريخ الحل

            $table->timestamps();
            $table->softDeletes(); // للحذف الآمن

            // Indexes للبحث السريع
            $table->index('status');
            $table->index('priority');
            $table->index('tracking_number');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaints');
    }
};
