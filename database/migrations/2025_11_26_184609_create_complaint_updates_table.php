<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('complaint_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('complaint_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // الموظف الذي أضاف التحديث

            $table->enum('old_status', [
                'pending', 'in_review', 'in_progress', 'resolved', 'rejected'
            ])->nullable();

            $table->enum('new_status', [
                'pending', 'in_review', 'in_progress', 'resolved', 'rejected'
            ]);

            $table->text('comment'); // تعليق/ملاحظة عن التحديث
            $table->boolean('is_public')->default(false); // هل التعليق ظاهر للمواطن؟

            $table->timestamps();

            $table->index('complaint_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('complaint_updates');
    }
};
