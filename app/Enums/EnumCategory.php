<?php

namespace App\Enums;

class EnumCategory
{
    const STATUS_PUBLIC = 1;
    const STATUS_PRIVATE = 2;

    const CATEGORY_OTHER_ID = 1;

    // ignore filter when id = 0
    const CATEGORY_IGNORE = 0;
}
