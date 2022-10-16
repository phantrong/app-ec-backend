<p>{{$input['name_staff']}}様</p>
<p>
    FESLIAISON運営事務局です。<br>
    予約開始30分前となりましたので、お知らせいたします。
</p>
----------------------------------------------------------------------------
@php
    $startTime = $input['reception_start_time'];
    $endTime = $input['reception_end_time'];
@endphp
<p>
    ●予約情報：<br>
    顧客 : {{$input['name_user']}} <br>
    予約店舗　：{{$input['store_name']}} <br>
    予約時間　: {{
            $startTime->month . "月".  $startTime->day . "日" . $startTime->hour . '時' .
            $startTime->minute . '分 〜 ' . $endTime->hour . '時' . $endTime->minute . '分'
        }}
</p>
----------------------------------------------------------------------------
<p>
    予約開始時間を過ぎて、店舗がお客様の入室を確認できない場合、自動的にキャンセルとなる可能性がございますので、余裕を持った入室をお願いいたします。
</p>
<p>
    ※当メールは送信専用メールアドレスから配信されております。 このままご返信いただいてもお答えできませんのでご了承下さい。
</p>
