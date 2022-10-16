<?php

namespace App\Services;

use App\Repositories\StoreMailVerification\StoreMailVerificationRepository;

class StoreMailVerificationService
{
    private $storeMailVerificationRepository;

    public function __construct(
        StoreMailVerificationRepository $storeMailVerificationRepository
    ) {
        $this->storeMailVerificationRepository = $storeMailVerificationRepository;
    }

    public function createOrUpdate($email, $token)
    {
        return $this->storeMailVerificationRepository->createOrUpdate($email, $token);
    }
}
