<?php

namespace App\Repositories\Admin;

use App\Models\Admin;
use App\Repositories\BaseRepository;

class AdminRepository extends BaseRepository implements AdminRepositoryInterface
{
    public function getModel()
    {
        return Admin::class;
    }

    public function getAdminByEmail($email)
    {
        return $this->model
        ->where('email', $email)
        ->first();
    }

    public function updateByEmail($email, array $data)
    {
        return $this->model
            ->where('email', $email)
            ->update($data);
    }

    public function getAllAdmin()
    {
        return $this->model->select('email')
            ->get();
    }
}
