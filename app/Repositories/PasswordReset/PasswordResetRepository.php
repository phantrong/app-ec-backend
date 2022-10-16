<?php

namespace App\Repositories\PasswordReset;

use App\Models\PasswordReset;
use App\Repositories\BaseRepository;
use Illuminate\Support\Arr;

class PasswordResetRepository extends BaseRepository implements PasswordResetRepositoryInterface
{
    public function getModel(): string
    {
        return PasswordReset::class;
    }

    public function getEmailByToken($token, $type)
    {
        return $this->model
            ->where('token', $token)
            ->where('type', $type)
            ->first();
    }

    public function deleteByEmail($email, $type)
    {
        return $this->model
            ->where('email', $email)
            ->where('type', $type)
            ->delete();
    }

    public function updateToken($email, $token, $type)
    {
        return $this->model
            ->where("email", $email)
            ->where('type', $type)
            ->update([
                'token' => $token,
                'created_at' => now()->format('Y-m-d H:i:s')
            ]);
    }

    public function createToken($data)
    {
        $result = $this->updateToken($data['email'], $data['token'], $data['type']);
        if (!$result) {
            $result = $this->model->create($data);
        }
        return $result;
    }
}
