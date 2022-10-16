<?php

namespace App\Repositories\PasswordReset;

use App\Repositories\RepositoryInterface;

interface PasswordResetRepositoryInterface extends RepositoryInterface
{
    public function getModel();

    /**
     * update token staff and customer by email and type
     *
     * @param string $email
     * @param string $token
     * @param int $type
     * @return mixed
     */
    public function updateToken($email, $token, $type);

    /**
     * delete record by email and type
     *
     * @param string $email
     * @param int $type
     * @return mixed
     */
    public function deleteByEmail($email, $type);

    /**
     * create or update a record
     *
     * @param array $data
     * @return mixed
     */
    public function createToken($data);
}
