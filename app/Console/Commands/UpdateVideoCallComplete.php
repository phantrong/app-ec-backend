<?php

namespace App\Console\Commands;

use App\Enums\EnumBookingStatus;
use App\Models\Booking;
use App\Repositories\Booking\BookingRepository;
use App\Repositories\Messenger\MessengerRepository;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateVideoCallComplete extends Command
{
    private BookingRepository $bookingRepository;
    private MessengerRepository $messengerRepository;

    public function __construct(
        BookingRepository $bookingRepository,
        MessengerRepository $messengerRepository
    ) {
        parent::__construct();
        $this->bookingRepository = $bookingRepository;
        $this->messengerRepository = $messengerRepository;
    }

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:VideoComplete';

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
     *
     */
    public function handle()
    {
        Log::channel('update_video_complete')->info(
            "====================UPDATE VIDEO CALL COMPLETE=================="
        );

        $this->bookingRepository->updateVideoComplete();
        $this->bookingRepository->updateActualTimeVideo();
        $roomIds = $this->bookingRepository->getBookingHasEndTime()->pluck('room_id');
        $this->messengerRepository->updateDisplayGroup($roomIds);
        Log::channel('update_video_complete')->info(
            '====================END INSERT REVENUE PRODUCT=================='
        );
    }
}
