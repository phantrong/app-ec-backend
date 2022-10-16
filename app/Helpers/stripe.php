<?php

function isRunPaymentStripe()
{
    return config('app.env') !== 'local';
}

function getLinkFESuccessPayment()
{
    return config('services.link_service_front') . 'cart/success';
}

function getLinkFECart($status)
{
    return config('services.link_service_front') . 'cart?status=' . $status;
}
