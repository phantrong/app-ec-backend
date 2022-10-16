<?php

namespace App\Console\Commands;

use App\Models\RevenueProduct;
use App\Repositories\Product\ProductRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class InsertRevenueProduct extends Command
{
    private ProductRepository $productRepository;

    public function __construct(ProductRepository $productRepository)
    {
        parent::__construct();
        $this->productRepository = $productRepository;
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'insert:revenueProduct';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $now = now()->format('Y-m-d');
        Log::channel('insert_revenue_product')->info(
            "====================INSERT REVENUE PRODUCT $now=================="
        );
        $dataInsert = $this->productRepository->getRevenueProductDaily()->toArray();
        RevenueProduct::insert($dataInsert);
        Log::channel('insert_revenue_product')->info(
            '====================END INSERT REVENUE PRODUCT=================='
        );
    }
}
