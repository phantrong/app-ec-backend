<p>{{$order['receiver_name'] . '様'}}</p>
<p>
    FESLIAISON運営事務局です。<br>
    ご注文いただきました商品を本日発送いたしました。<br> <br>
    今回のご注文商品は下記の通りです。
</p>
—————-【注文情報】—————–
<p>●商品番号:</p>
-------------------------------------------------------------------
<p>
    配送情報 <br>
    お届け先：{{$order['address_01'] . $order['address_02'] . $order['address_03'] . $order['address_04']}} <br>
    送り主：{{$order['receiver_name']}}
</p>
-------------------------------------------------------------------
<p>
    注文管理名 ： {{$order['store_name']}} <br>
    商品名:
@foreach($order['order_items'] as $item)
    <p>
        {{$item['product_class']['product']['name']}}
        {{$item['product_class']['get_product_type_deleted'][0]['type_name'] ?? ''}}
        {{$item['product_class']['get_product_type_deleted'][1]['type_name'] ?? ''}}
    </p>
    @endforeach
    </p>
    -------------------------------------------------------------------
    <p>税込金額：{{$order['total_payment']}} 円</p>
    <p>
        ※天候・交通状況により、入荷が遅れ発送日が変更となる場合ございます。
        予めご了承頂きますようお願い致します。<br>

        商品到着まで今しばらくお待ちくださいませ。
        ご不明点などございましたら、お気軽にご連絡ください。<br>

        よろしくお願いします。
    </p>

