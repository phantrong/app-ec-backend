<p>
    この度はFESLIAISONではにご注文頂き誠にありがとうございます。 <br>
    確認のためにご注文詳細をお送りいたします。 <br>
    何かご不明な点等ございましたら、fesliaison@gmail.comまでご連絡ください。
</p>
<p>
    @php
        $orderDate = \Carbon\Carbon::parse($order['ordered_at']);
    @endphp
    ■ご注文番号 ：{{$order['order_code']}} <br>
    注文日時 ：{{$orderDate->year}}年{{$orderDate->month}}月{{$orderDate->day}}日{{$orderDate->hour}}時{{$orderDate->minute}}  分 <br>
    合計金額　　： {{$order['total_payment']}}円 <br>
    支払い方法　：クレジット決済
</p>
<p>
    ※ご注文内容に誤りがある場合には金額が変更となる恐れがございます。<br>
    ※受付確定日はデザインデータに不備がない、及び入金の確認が完了した時点となります。<br>
    ※ご入稿頂いたデザインに不備がある場合には【メール】fesliaison@gmail.com にてご連絡させて頂いております。<br>
</p>
===================================================================
<br>
<p>
    配送情報: <br>
    ・お届け先：{{$order['shipping']['address_01'] . $order['shipping']['address_02'].
    $order['shipping']['address_03'] . $order['shipping']['address_04']}} <br>
    ・送り主：{{$order['shipping']['receiver_name']}}
</p>

-------------------------------------------------------------------
@foreach($order['sub_orders'] as $subOrder)
   <p>
       店舗名  ： {{$subOrder['store']['name']}} <br>
       商品名 ( 数量):
   <ul>
       @foreach($subOrder['order_items'] as $item)
           <li>
               {{$item['product_class']['product']['name']}}
               {{$item['product_class']['get_product_type_deleted'][0]['type_name'] ?? ''}}
               {{$item['product_class']['get_product_type_deleted'][1]['type_name'] ?? ''}} : {{$item['quantity']}}
           </li>
       @endforeach
   </ul>
   </p>
@endforeach

