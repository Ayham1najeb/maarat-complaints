<?php

namespace Database\Seeders;

use App\Models\Area;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class AreaSeeder extends Seeder
{
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        Area::truncate();
        Schema::enableForeignKeyConstraints();

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
    }
}
