<?php

namespace App\Repositories\Admin;

use App\Repositories\RepositoryInterface;

interface AdminRepositoryInterface extends RepositoryInterface
{
    public function getModel();

     /**
     * get admin by email
     *
     * @param string $email
     * @return object
     */
    public function getAdminByEmail($email);

    /**
     * update admin by email
     *
     * @param string $email
     * @param array $data
     * @return object
     */
    public function updateByEmail($email, array $data);

    /**
     * getAllAdmin
     *
     * @return object
     */
    public function getAllAdmin();
}
