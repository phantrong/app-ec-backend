<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BankHistory extends CoreModel
{
    use HasFactory, SoftDeletes;

    protected $table = 'dtb_bank_histories';
    protected $guarded = [];

    public function histories()
    {
        return $this->hasMany(Payout::class, 'external_account_id', 'stripe_bank_id');
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class, 'bank_id', 'id');
    }

    public function bankBranch()
    {
        return $this->belongsTo(BankBranch::class, 'branch_id');
    }
}
