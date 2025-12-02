<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complaint_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->constrained('users')->cascadeOnDelete(); // الموظف المكلف
            $table->foreignId('assigned_by')->constrained('users')->cascadeOnDelete(); // من عيّنه

            $table->text('notes')->nullable(); // ملاحظات للموظف
            $table->enum('status', [
                'pending',      // لم يبدأ بعد
                'working',      // قيد العمل
                'completed',    // مكتمل
                'cancelled'     // ملغي
            ])->default('pending');

            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();

            $table->index('complaint_id');
            $table->index('assigned_to');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
