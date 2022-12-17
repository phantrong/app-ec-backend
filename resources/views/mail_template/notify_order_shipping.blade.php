<p>Xin chào <b>{{$order['receiver_name']}}</b></p>
<p>
    Cảm ơn bạn đã luôn sử dụng hệ thống MY CART. <br>
    Đơn hàng mã {{ $order['order_code'] }} của bạn đã bắt đầu được vận chuyển. Hãy để ý điện thoại của bạn để nhận hàng.<br> <br>
</p>
—————-【Thông tin đơn hàng】—————–
<p>
    Địa chỉ nhận hàng: {{$order['address']}} <br>
    Tên người nhận: {{$order['receiver_name']}}
</p>
-------------------------------------------------------------------
<p>
    Tên cửa hàng: {{$order['store_name']}} <br>
    Các sản phẩm:
    @foreach($order['order_items'] as $item)
    <p>
        {{$item['product']['name']}}
    </p>
    @endforeach
    </p>
    -------------------------------------------------------------------
    <p>Tổng tiền thanh toán: {{number_format(ceil($order['total_payment']))}} VNĐ</p>
    <p>
        ※Ngày giao hàng có thể thay đổi do điều kiện thời tiết và giao thông.<br>
        Vui lòng đợi một lúc cho đến khi sản phẩm đến.
        Nếu bạn có bất kỳ câu hỏi nào, xin vui lòng liên hệ với chúng tôi.<br>
        Cảm ơn bạn rất nhiều.
    </p>
</p>
