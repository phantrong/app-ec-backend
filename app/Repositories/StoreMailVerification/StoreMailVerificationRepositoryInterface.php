<?php

namespace App\Repositories\StoreMailVerification;

use App\Repositories\RepositoryInterface;

interface StoreMailVerificationRepositoryInterface extends RepositoryInterface
{
    public function getModel();

    /**
     * createOrUpdate
     *
     * @param  string $email
     * @param  string $token
     * @return void
     */
    public function createOrUpdate($email, $token);

    /**
     * getEmailByToken
     *
     * @param  string $token
     * @return object
     */
    public function getEmailByToken($token);
}
