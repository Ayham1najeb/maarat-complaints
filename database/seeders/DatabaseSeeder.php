<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Area;
use App\Models\Category;
use App\Models\Complaint;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Skip if data already exists
        if (Category::count() > 0 && Area::count() > 0) {
            $this->command->info('✅ البيانات موجودة مسبقاً!');
            return;
        }

        // 2. إنشاء المناطق
        $areas = [
            'وسط المدينة',
            'حي الشمال',
            'حي الجنوب',
            'حي الشرق',
            'حي الغرب',
            'المنطقة الصناعية',
            'السوق القديم',
            'الطريق العام',
        ];

        foreach ($areas as $areaName) {
            Area::firstOrCreate(['name' => $areaName]);
        }

        // 3. إنشاء التصنيفات
        $categories = [
            ['name' => 'Cleaning', 'name_ar' => 'نظافة', 'icon' => '🧹', 'color' => '#10B981'],
            ['name' => 'Lighting', 'name_ar' => 'إنارة', 'icon' => '💡', 'color' => '#F59E0B'],
            ['name' => 'Roads', 'name_ar' => 'طرقات', 'icon' => '🛣️', 'color' => '#6B7280'],
            ['name' => 'Water', 'name_ar' => 'مياه', 'icon' => '💧', 'color' => '#3B82F6'],
            ['name' => 'Sewage', 'name_ar' => 'صرف صحي', 'icon' => '🚰', 'color' => '#8B5CF6'],
            ['name' => 'Electricity', 'name_ar' => 'كهرباء', 'icon' => '⚡', 'color' => '#EF4444'],
            ['name' => 'Other', 'name_ar' => 'أخرى', 'icon' => '📋', 'color' => '#6366F1'],
        ];

        foreach ($categories as $index => $category) {
            Category::firstOrCreate(
                ['name' => $category['name']],
                array_merge($category, ['order' => $index + 1])
            );
        }

        $this->command->info('✅ تم إنشاء البيانات الأساسية بنجاح!');
        $this->command->info('📂 التصنيفات: ' . Category::count());
        $this->command->info('📍 المناطق: ' . Area::count());
    }
}
