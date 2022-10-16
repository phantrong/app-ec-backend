<?php

namespace Database\Seeders;

use App\Enums\EnumBrand;
use App\Enums\EnumCategory;
use App\Models\Brand;
use App\Models\Category;
use Illuminate\Database\Seeder;

class CreateCategoryAndBrandOther extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Category::updateOrCreate([
            'id' => 1,
            'status' => EnumCategory::STATUS_PUBLIC,
            'name' => 'その他'
        ]);
        Brand::updateOrCreate([
            'id' => 1,
            'name' => 'その他',
            'status' => EnumBrand::STATUS_PUBLIC
        ]);
    }
}
