<?php

namespace Database\Seeders;

use App\Models\Staff;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StoreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Store::updateOrCreate([
            'id' => 1,
            'name' => "Cửa hàng của Trọng",
            'code' => "SHOP0001",
            'status' => 4,
            'description' => "Cửa hàng của Trọng bán tất cả mọi thứ",
            'address' => "Hà nội",
            'avatar' => "https://bizweb.dktcdn.net/100/149/780/articles/cua-hang-tien-loi.jpg?v=1651504364267",
            'cover_image'  => "https://bizweb.dktcdn.net/100/149/780/articles/cua-hang-tien-loi.jpg?v=1651504364267"
        ]);

        Staff::updateOrCreate([
            'id' => 1,
            'phone' => '0123456789',
            'status' => 1,
            'email' => 'phantrong001@gmail.com',
            'password' => Hash::make("123456789"),
            'store_id' => 1,
            'is_owner' => 1,
            'name' => 'Trọngggg'
        ]);

        Store::updateOrCreate([
            'id' => 2,
            'name' => "Cửa hàng của Khánh",
            'code' => "SHOP0002",
            'status' => 4,
            'description' => "Cửa hàng của Khánh bán tất cả mọi thứ",
            'address' => "Hà nội",
            'avatar' => "https://bizweb.dktcdn.net/100/149/780/articles/cua-hang-tien-loi.jpg?v=1651504364267",
            'cover_image'  => "https://bizweb.dktcdn.net/100/149/780/articles/cua-hang-tien-loi.jpg?v=1651504364267"
        ]);

        Staff::updateOrCreate([
            'id' => 2,
            'phone' => '0123456789',
            'status' => 1,
            'email' => 'kh2000987@gmail.com',
            'password' => Hash::make("123456789"),
            'store_id' => 2,
            'is_owner' => 1,
            'name' => 'Khánhhhh'
        ]);
    }
}
