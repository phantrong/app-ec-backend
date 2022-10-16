<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class EnumStore extends Enum
{
    // waiting admin confirm
    const STATUS_NEW = 1;

    //admin block account
    const STATUS_BLOCKED = 2;

    // waiting stripe confirm
    const STATUS_WAITING_STRIPE = 3;

    //status admin has confirm
    const STATUS_CONFIRMED = 4;

    // admin un approved
    const STATUS_CANCEL = 5;

    //status stripe fail
    const STATUS_FAIL = 6;

    const COMMISSION_DEFAULT = 40;

    const PREFIX_CODE = 'S_';
}
