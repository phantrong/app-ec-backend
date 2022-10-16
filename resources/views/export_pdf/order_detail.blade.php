<!doctype html>
<html lang="en"
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.3.1/dist/css/bootstrap.min.css">
    <style>
        body {
            font-family: 'sazanami-mincho';
            padding: 20px 50px;
        }
        .title {
            background-color: #EFC028;
        }
        .store-info {
            text-align: right;
        }
        .store-info .store td {
            text-align: left;
        }
        .total-payment {
            text-decoration: underline;
            font-weight: bold;
        }
        .heading {
            background-color: #EFC028;
        }
        th {
            text-align: center;
        }
        @font-face {
            font-family: 'sazanami-mincho';
            font-style: normal;
            font-weight: 400;
            src: url({{ storage_path('fonts\sazanami-mincho.ttf') }}) format("truetype");
        }
    </style>

</head>
<body>
    <p class="text-center title">
       支払明細書
    </p>
    <div class="date text-right">
        {{now()->year}}年{{now()->month}}月 {{now()->day}}日
    </div>

    <div class="row store-info">
        <div class="col-7"></div>
        <div class="store col-5">
            <table class="table table-bordered store">
                <tr>
                    <td>会社名</td>
                    <td>{{$subOrder->company}}</td>
                </tr>
                <tr>
                    <td>店舗名</td>
                    <td>{{$subOrder->store_name}}</td>
                </tr>
                <tr>
                    <td>住所</td>
                    <td>{{$subOrder->address}}</td>
                </tr>
                <tr>
                    <td>電話番号</td>
                    <td>{{$subOrder->phone}}</td>
                </tr>
                <tr>
                    <td>Fax:</td>
                    <td>{{$subOrder->fax}}</td>
                </tr>
            </table>

        </div>
    </div>

    <div class="bill">
        <p>下記の通りお支払い申し上げます。</p>
        <p class="total-payment">支払金額: &nbsp; ¥{{$subOrder->total_payment}}</p>
        <p>注文ID: {{$subOrder->sub_order_code}}</p>
        <table class="table table-bordered">
            <thead class="heading">
            <tr>
                <th>番号</th>
                <th>商品名</th>
                <th>数量</th>
                <th>単価（円）</th>
                <th>金額（円)</th>
            </tr>
            </thead>
            <tbody>
            @foreach($subOrder->orderItems as $index => $item)
                @php
                    $configTypes = $item->productClass->productTypeConfigs;
                    $typeName = '';
                    foreach ($configTypes as $type) {
                        $typeName .= ' ' . $type->type_name;
                    }
                @endphp
                <tr>
                    <td>{{++$index}}</td>
                    <td>{{$item->productClass->product->name . $typeName}}</td>
                    <td>{{$item->quantity}}</td>
                    <td>{{$item->price}}</td>
                    <td>{{$item->quantity * $item->price}}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

</body>
</html>
