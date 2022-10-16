<?php

namespace Database\Seeders;

use App\Enums\EnumCustomer;
use App\Enums\EnumStaff;
use App\Models\Customer;
use App\Models\Staff;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UpdateAccountShop extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $staffs = Staff::where('is_owner', EnumStaff::IS_OWNER)->get();
        $dataShop = [];
        foreach ($staffs as $staff) {
            $dataShop[] = [
                'email' => $staff->email,
                'password' => $staff->password,
                'store_id' => $staff->store_id,
                'status' => EnumCustomer::STATUS_ACTIVE,
                'name' => $staff->name,
                'surname' => $staff->name,
                'name_furigana' => $staff->name,
                'surname_furigana' => $staff->name,
                'phone' => $staff->phone,
                'gender' => $staff->gender,
                'birthday' => '1999-10-10',
                'send_mail' => EnumCustomer::SEND_MAIL,
                'created_at' => $staff->created_at,
                'updated_at' => $staff->updated_at
            ];
        }
        Customer::insert($dataShop);
    }
}
