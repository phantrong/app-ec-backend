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
            'name' => 'Khác'
        ]);
        Category::updateOrCreate([
            'id' => 2,
            'image_path' => 'https://dosi-in.com/images/detailed/42/CDL10_1.jpg',
            'status' => EnumCategory::STATUS_PUBLIC,
            'name' => 'Áo'
        ]);
        Category::updateOrCreate([
            'id' => 3,
            'image_path' => 'https://onoff.vn/media/catalog/product/cache/ecd9e5267dd6c36af89d5c309a4716fc/18BS22S041.jpg',
            'status' => EnumCategory::STATUS_PUBLIC,
            'name' => 'Quần'
        ]);
        Category::updateOrCreate([
            'id' => 4,
            'image_path' => 'https://cf.shopee.vn/file/6198610da7999ce65ac18c9052b1b2ca',
            'status' => EnumCategory::STATUS_PUBLIC,
            'name' => 'Giày'
        ]);
        Brand::updateOrCreate([
            'id' => 1,
            'category_id' => 1,
            'name' => 'Khác',
            'status' => EnumBrand::STATUS_PUBLIC
        ]);
        Brand::updateOrCreate([
            'id' => 2,
            'name' => 'Áo khoác',
            'category_id' => 2,
            'status' => EnumBrand::STATUS_PUBLIC
        ]);
        Brand::updateOrCreate([
            'id' => 3,
            'name' => 'Quần jeans',
            'category_id' => 3,
            'status' => EnumBrand::STATUS_PUBLIC
        ]);
        Brand::updateOrCreate([
            'id' => 4,
            'name' => 'Giày Sneaker',
            'category_id' => 4,
            'status' => EnumBrand::STATUS_PUBLIC
        ]);
    }
}
