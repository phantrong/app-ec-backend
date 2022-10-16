<?php

namespace App\Console\Commands;

use App\Enums\EnumLiveStream;
use App\Models\LiveStream;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use MyCLabs\Enum\Enum;

class CancelLivestream extends Command
{
    const HOUR_LIMIT = 10;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cancel:livestream';

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
        $now = now()->format('Y-m-d H:i:s');
        Log::channel('cancel_livestream')->info("==================== CANCEL LIVESTREAM $now ==================");
        $this->getLivestreamCancel();
        Log::channel('cancel_livestream')->info('==================== END ==================');
    }

    public function getLivestreamCancel()
    {
        $dateCheck = now()->subHour(self::HOUR_LIMIT)->format("Y-m-d H:i:s");
        return LiveStream::where('start_time', '<', $dateCheck)
            ->where('status', EnumLiveStream::STATUS_NOT_START)
            ->update([
                'status' => EnumLiveStream::STATUS_CANCEL
            ]);
    }
}
