<p>{{$order['receiver_name'] . '様'}}</p>
<p>
    FESLIAISON運営事務局です。<br>
    先日は当店をご利用いただき誠にありがとうございます。<br>
    お届けした商品はいかがでしたか?
</p>
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
        お気づきの点があれば、当店までお気軽にご連絡くださいませ。
        <a href="{{config('services.link_service_front') . 'stores/' . $order['store_id']}}">
            {{config('services.link_service_front') . 'stores/' . $order['store_id']}}
        </a>
        <br>
        最後になりますが、この度は当店をご利用いただきまして <br>
        誠にありがとうございました。 <br>
        またのご来店をスタッフ一同心よりお待ちしております。
    </p>
