<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

final class EnumStaff extends Enum
{
    const STATUS_ACCESS = 1;
    const STATUS_BLOCKED = 2;

    const IS_OWNER = 1;
    const IS_STAFF = 0;
}
