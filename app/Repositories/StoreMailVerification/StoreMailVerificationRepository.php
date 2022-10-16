<?php

namespace App\Repositories\StoreMailVerification;

use App\Models\StoreMailVerification;
use App\Repositories\BaseRepository;

class StoreMailVerificationRepository extends BaseRepository implements StoreMailVerificationRepositoryInterface
{
    public function getModel(): string
    {
        return StoreMailVerification::class;
    }

    public function createOrUpdate($email, $token)
    {
        $result = $this->model->where('email', $email)->first();

        if ($result) {
            $result->token = $token;
            $result->save();
        } else {
            $this->model->create([
                'email' => $email,
                'token' => $token
            ]);
        }
    }

    public function getEmailByToken($token)
    {
        return $this->model->select('email')
            ->where('token', $token)->first();
    }
}
