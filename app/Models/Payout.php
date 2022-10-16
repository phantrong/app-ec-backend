<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payout extends CoreModel
{
    use HasFactory;

    protected $table = 'dtb_payouts';

    protected $hidden = ['stripe_payout_id'];

    protected $fillable = [
        'stripe_account_id',
        'stripe_bank_id',
        'currency',
        'method',
        'amount',
        'source_type',
        'status',
        'type',
        'automatic',
        'arrival_date',
        'created',
    ];

    public function bankHistory()
    {
        return $this->belongsTo(BankHistory::class, 'stripe_bank_id', 'external_account_id');
    }
}
