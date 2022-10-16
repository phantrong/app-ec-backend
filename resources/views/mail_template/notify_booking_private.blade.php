<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Document</title>
</head>
<body>
@php
    $startTime = \Carbon\Carbon::parse($booking['reception_date'] . ' ' .$booking['reception_start_time']);
    $endTime = \Carbon\Carbon::parse($booking['reception_date'] . ' ' . $booking['reception_end_time']);
@endphp
<p>
    FESLIAISON運営事務局です。 この度はFESLIAISONをご利用いただきまして、誠にありがとうございます。
</p>
<hr>
<p><b> 予約情報 </b></p>
<p> 予約店舗: {{$booking['store_name']}}</p>
<p>
    予約時間: {{
            $startTime->month . "月".  $startTime->day . "日" . $startTime->hour . '時' .
            $startTime->minute . '分 〜 ' . $endTime->hour . '時' . $endTime->minute . '分'
        }}
</p>
<p>
    公開モードから非公開モードに変更されたことをお知らせいたします。
</p>
<p>
    最後になりますが、この度は当店をご利用いただきまして誠にありがとうございました。
</p>
<p>
    またのご来店をスタッフ一同心よりお待ちしております。
</p>
</body>
</html>
