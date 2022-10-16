<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class EnumProduct extends Enum
{
    const MEDIA_TYPE_IMAGE = 1;
    const MEDIA_TYPE_VIDEO = 2;

    // specified number of days is new product
    const DAY_PRODUCT_NEW = 7;

    const STATUS_PUBLIC = 1;
    const STATUS_NO_PUBLIC = 2;
    const STATUS_VIOLATION = 3;
    const STATUS_AVAILABLE = 4;
    const STATUS_UN_AVAILABLE = 5;
}
