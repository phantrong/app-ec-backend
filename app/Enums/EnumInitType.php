<?php

namespace App\Enums;

use BenSampo\Enum\Enum;

/**
 * @method static static perPage()
 */
final class EnumInitType extends Enum
{
    const PER_PAGE = 10;

    const LIVESTREAM = 1;
    const VIDEO_CALL = 2;

    const MALE = 1;
    const FEMALE = 2;

    const TYPE_USER = 1;
    const TYPE_SHOP = 2;
}
