<?php

namespace App\Console\Commands;

use App\Models\RevenueOrder;
use App\Repositories\SubOrder\SubOrderRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class InsertRevenueOrder extends Command
{
    private SubOrderRepository $subOrderRepository;

    public function __construct(SubOrderRepository $subOrderRepository)
    {
        parent::__construct();
        $this->subOrderRepository = $subOrderRepository;
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'insert:revenueOrder';

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
        $now = now()->subDay(1)->format('Y-m-d');
        Log::channel('insert_revenue_order')->info("====================INSERT REVENUE ORDER $now==================");
        $dataInsert = $this->subOrderRepository->statisticRevenueOrderDaily(null, null, true)->toArray();
        RevenueOrder::insert($dataInsert);
        Log::channel('insert_revenue_order')->info('====================END INSERT REVENUE ORDER==================');
    }
}
