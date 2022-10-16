<?php

namespace App\Console\Commands;

use App\Models\RevenueAge;
use App\Repositories\SubOrder\SubOrderRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InsertRevenueAge extends Command
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
    protected $signature = 'insert:revenueAge';

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
        Log::channel('insert_revenue_age')->info("====================INSERT REVENUE AGE $now==================");
        $dataInsert = $this->subOrderRepository->statisticRevenueAgeDaily()->toArray();
        RevenueAge::insert($dataInsert);
        Log::channel('insert_revenue_age')->info('====================END INSERT REVENUE AGE==================');
    }
}
