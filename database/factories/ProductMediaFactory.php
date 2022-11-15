<?php

namespace Database\Factories;

use App\Models\ProductMedia;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProductMedia>
 */
class ProductMediaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = ProductMedia::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition()
    {
        $arrayImages = [
            'https://product.hstatic.net/200000201725/product/_mg_5178_61041e0a3cf144ccbd77afe1e83adb84_master.jpg',
            'https://lzd-img-global.slatic.net/g/p/16e4debc533c2a975a24959cca6a5b24.jpg_720x720q80.jpg_.webp',
            'https://file.hstatic.net/1000213511/file/giay-nam-0112-5t40__2__grande.jpg',
            'https://shoptretho.com.vn/upload/image/product/20151119/quan-ni-mong-thu-carter-4.jpg',
            'https://img.zanado.com/media/catalog/product/cache/all/thumbnail/700x817/7b8fef0172c2eb72dd8fd366c999954c/1/_/quan_short_jean_xan_gau_ca_tinh_1f21.jpg',
            'https://img.cdn.vncdn.io/nvn/ncdn/store2/77662/pc/content480956/quan_jean_nu_liin_clothing_(6).jpg',
            'https://cf.shopee.vn/file/14f46de3a546e2d1b05f31b947a8e6b1',
            'https://product.hstatic.net/1000357687/product/2_cfbadb80d7de406dac2e7a37f8e28512_master.jpg',
        ];

        return [
            'product_id' => $this->faker->numberBetween(1, 20),
            'media_type' => 1,
            'media_path' => $arrayImages[array_rand($arrayImages)]
        ];
    }
}
