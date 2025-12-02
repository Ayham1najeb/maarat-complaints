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
        // 1. إنشاء مستخدمين
        $admin = User::create([
            'name' => 'المدير العام',
            'email' => 'admin@complaint.sy',
            'phone' => '0933123456',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $employee = User::create([
            'name' => 'موظف النظافة',
            'email' => 'employee@complaint.sy',
            'phone' => '0944123456',
            'password' => Hash::make('password'),
            'role' => 'employee',
        ]);

        $citizen = User::create([
            'name' => 'محمد أحمد',
            'email' => 'citizen@example.com',
            'phone' => '0955123456',
            'password' => Hash::make('password'),
            'role' => 'citizen',
        ]);

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
            Area::create(['name' => $areaName]);
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
            Category::create(array_merge($category, ['order' => $index + 1]));
        }

        // 4. إنشاء شكاوي تجريبية
        $complaints = [
            [
                'citizen_name' => 'أحمد محمود',
                'citizen_phone' => '0966111222',
                'category_id' => 1,
                'area_id' => 1,
                'title' => 'تراكم القمامة في الشارع الرئيسي',
                'description' => 'يوجد تراكم كبير للقمامة عند مدخل الحي منذ أسبوع',
                'location_address' => 'شارع الجامع الكبير',
                'status' => 'pending',
                'priority' => 'high',
            ],
            [
                'citizen_name' => 'فاطمة علي',
                'citizen_phone' => '0977222333',
                'category_id' => 2,
                'area_id' => 2,
                'title' => 'عطل في إنارة الشارع',
                'description' => 'الإنارة العامة معطلة في شارعنا مما يسبب خطورة ليلاً',
                'location_address' => 'شارع السوق',
                'status' => 'in_review',
                'priority' => 'urgent',
            ],
            [
                'citizen_name' => 'خالد يوسف',
                'citizen_phone' => '0988333444',
                'category_id' => 3,
                'area_id' => 3,
                'title' => 'حفرة كبيرة في الطريق',
                'description' => 'حفرة خطيرة في وسط الشارع تسبب حوادث',
                'location_address' => 'شارع الشام',
                'status' => 'in_progress',
                'priority' => 'high',
            ],
            [
                'citizen_name' => 'سارة حسن',
                'citizen_phone' => '0999444555',
                'category_id' => 4,
                'area_id' => 4,
                'title' => 'انقطاع المياه',
                'description' => 'المياه مقطوعة منذ يومين في كامل الحي',
                'location_address' => 'حي الجديدة',
                'status' => 'resolved',
                'priority' => 'urgent',
                'resolved_at' => now()->subDays(1),
            ],
        ];

        foreach ($complaints as $complaintData) {
            Complaint::create(array_merge($complaintData, [
                'tracking_number' => 'CM' . strtoupper(Str::random(10)),
            ]));
        }

        $this->command->info('✅ تم إنشاء البيانات التجريبية بنجاح!');
        $this->command->info('📧 Admin: admin@complaint.sy | Password: password');
        $this->command->info('📧 Employee: employee@complaint.sy | Password: password');
        $this->command->info('📧 Citizen: citizen@example.com | Password: password');
    }
}
