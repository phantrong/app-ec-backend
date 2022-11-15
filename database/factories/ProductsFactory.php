<?php

namespace Database\Factories;

use App\Models\Products;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Model>
 */
class ProductsFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Products::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $arrayStoreIds = [1, 2];
        $arrayBrandIds = [1, 2, 3, 4];
        $arrayCategoryIds = [1, 2, 3, 4];

        return [
            'name' => $this->faker->name(),
            'store_id' => $arrayStoreIds[array_rand($arrayStoreIds)],
            'status' => 4,
            'description' => $this->faker->address(),
            'brand_id' => $arrayBrandIds[array_rand($arrayBrandIds)],
            'category_id' => $arrayCategoryIds[array_rand($arrayCategoryIds)],
            'price' => $this->faker->numberBetween(5000000, 10000000),
            'discount' => $this->faker->numberBetween(1000000, 2000000)
        ];
    }
}
