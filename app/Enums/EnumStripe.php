<?php

namespace App\Enums;

class EnumStripe
{
    const IMAGE_PASSPORT = 1;
    const IMAGE_DRIVER = 2;
    const IMAGE_RESIDENCE = 3;
    const IMAGE_NUMBER_CARD = 4;
    const IMAGE_RESIDENCE_CERTIFICATE = 5;
    const IMAGE_OTHER = 6;

    const STATUS_PAYOUT_HISTORY_PENDING = 1;
    const STATUS_PAYOUT_HISTORY_PAID = 2;
    const STATUS_PAYOUT_HISTORY_FAILED = 3;

    const ARRAY_IMAGE = [1, 2, 3, 4, 5, 6];

    const PER_PAGE_LIST_ACCOUNT = 10;
    const PER_PAGE_PAYOUT_HISTORY = 8;
    const PER_PAGE_PAYOUT_HISTORY_CMS = 11;

    const AREA_CODE_JP = '+81';

    const ERROR_PHONE_NUMBER = 'is not a valid phone number';
    const ERROR_ADDRESS = 'address for Japan';
    const ERROR_BANK_NUMBER = 'bank';
    const ERROR_BRANCH_NUMBER = 'branch';

    //set time out stripe when fail
    const TIME_OUT_CHECKOUT = 1;

    const BANK_TEST = 'Atest';
    const ROUTING_TEST = '1100000';
}
