<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class EnumSubOrder extends Enum
{
    const TEXT_STATUS = [
        '確認中',
        '送品待ち',
        '送品中',
        '受け取り済',
        'キャンセル'
    ];

    const STATUS_WAIT_CONFIRM = 1;
    const STATUS_WAIT_FOR_GOOD = 2;
    const STATUS_SHIPPING = 3;
    const STATUS_SHIPPED = 4;
    const STATUS_CANCEL = 5;

    const STATUS = [
        'WAIT_FOR_GOOD' => 2,
        'SHIPPING' => 3,
        'SHIPPED' => 4,
    ];

    // unit statistic revenue
    const UNIT_DAY = 1;
    const UNIT_MONTH = 2;
    const UNIT_YEAR = 3;

    const MAX_DAY_FILTER = 31;
}
