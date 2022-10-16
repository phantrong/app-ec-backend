<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class StoreMailVerification extends CoreModel
{
    use HasFactory;

    protected $table = 'store_mail_verifications';

    protected $fillable = [
        'email',
        'token',
    ];
}
