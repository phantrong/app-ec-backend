<?php
return [
    'common' => [
        'require' => 'A001',
        'not_valid' => 'A002',
        'token_invalid' => 'A003'
    ],
    'cart' => [
        'product_class_not_found' => 'B001',
        'product_less_than_0' => 'B002',
        'products_quantity_sale_limit' => 'B003',
        'order_not_item' => 'B005',
        'not_enough_products_quantity' => 'C001',
        'not_add_order_error' => 'C002',
        'product_out_of_stock' => 'C003',
    ],

    'account' => [
        'customer_not_exists' => "D001",
        'customer_block' => "D002",
        'exists' => "D003",
        'un_verify' => "D004",
        'processing' => "D005"
    ],

    'password' => [
        'not_valid' => 'E001'
    ],

    'link' => [
        'not_valid' => 'F001'
    ],

    'product' => [
        'sold' => 'G001',
        'not_found' => 'G002',
    ],

    'category' => [
        'used' => 'H001'
    ],

    'staff' => [
        'not_found' => 'I001'
    ],

    'booking' => [
        'not_found' => 'J001',
        'not_valid' => 'J002',
    ],

    'store' => [
        'not_found' => 'K001',
    ],

    'calendar_staff' => [
        'not_found' => 'L001',
        'not_valid' => 'L002',
        'empty' => 'L003',
        'available' => 'L004',
    ],

    'customer' => [
        'not_found' => 'M001',
    ],

    'order' => [
        'not_found' => 'N001',
    ],

    'stripe' => [
        'errors' => [
            'phone_number_valid' => 'S001',
            'address' => 'S002',
            'bank_number' => 'S003',
            'stripe_error' => 'S004', // error unknown
        ]
    ],
];
