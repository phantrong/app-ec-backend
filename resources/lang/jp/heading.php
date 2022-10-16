<?php

return [
    // file csv revenue order
    'revenue_order' => [
        'date' => "取引日",
        'number_order' => '購入件数（件)',
        'customer_male' => '男性顧客',
        'customer_female' => '女性顧客',
        'customer_unknown' => '不明（会員)',
        'customer_not_login' => '不明（非会員)',
        'revenue' => '購入合計(手数料込み)',
        'revenue_actual' => '購入合計(手数料なし)',
        'average' => '購入平均(円)',
        'file_name' => '売上管理_'
    ],

    // file csv revenue product
    'revenue_product' => [
        'index' => '番号',
        'name' => '商品名',
        'total_order' => '購入件数（件)',
        'total_product' => '数量（個)',
        'revenue' => '金額（円)',
        'file_name' => '商品別集計_'
    ],

    // file csv revenue by age
    'revenue_age' => [
        'age' => '年代',
        'total_order' => '購入件数（件)',
        'revenue' => '購入合計（円)',
        'average' => '購入平均（円)',
        'file_name' => '年代別集計_'
    ],
];
