<?php

namespace App\Enums;

class EnumCustomer
{
    const STATUS_CREATE = 1;
    const STATUS_ACTIVE = 2;
    const STATUS_BLOCKED = 3;

    const GENDER_MALE = 1;
    const GENDER_FEMALE = 2;
    const GENDER_UN_KNOWN = 3;

    // number age milestones of customers
    const NUMBER_AGE_MILESTONE = 11;
    const AGE_COEFFICIENT = 10;

    const SEND_MAIL = 1;

    const STATUS_SIGNUP_NEW = 1;
    const STATUS_SIGNUP_FAILED = 2;
}
