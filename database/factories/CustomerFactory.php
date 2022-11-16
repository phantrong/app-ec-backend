<?php

namespace Database\Factories;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Customer>
 */
class CustomerFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Customer::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        return [
            'status' => 2,
            'name' => $this->faker->name(),
            'email' => $this->faker->email(),
            'password' => Hash::make("123456789"),
            'phone' => '0123456789',
            'gender' => 1,
            'birthday' => '1990-01-01',
            'send_mail' => 1,
            'address' => 'Hà nội'
        ];
    }
}
