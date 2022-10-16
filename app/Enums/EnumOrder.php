<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class EnumOrder extends Enum
{
    const STATUS_NEW = 1;
    const STATUS_PAID = 2;
    const STATUS_DONE = 3;

    const ARRAY_STATUS_SUCCESS = [self::STATUS_PAID, self::STATUS_DONE];

    const PAYMENT_SUCCESS = 1;
    const PAYMENT_ERROR = 2;
    const PAYMENT_ERROR_RESULT = 3;
    const PAYMENT_ERROR_CANCEL = 4;
    const PAYMENT_ERROR_UNPAID = 5;
}
