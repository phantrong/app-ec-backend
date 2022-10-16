<p><b>{{ $customer['name'] }}様</b></p>
<p>
    いつもFESLIAISON運営事務局をご利用いただきありがとうございます。
    ご送付いただきました出店申込につきまして、
    FESLIAISON運営事務局より承認されましたので、ご案内致します。
</p>
<hr>
@if (isset($fakePassword) && $fakePassword)
<p>
    仮パスワードが再発行されました。下記URLからログインし、新しいパスワードを設定してください。
</p>
<p>
    仮パスワード ：{{ $fakePassword }}
</p>
<p>
    ログイン画面へ ：<a href="{{config('services.link_service_front_shop') . 'login'}}">
        <b>{{config('services.link_service_front_shop') . 'login'}}</b>
    </a>
</p>
@else
<p>
    購入者専用ページのパスワードは、店舗管理画面にログインする際にも必要となります。 必ず忘れないよう、保管をお願いいたします。
    お客様のご利用を心よりお待ちしております。
</p>
<p>
    ログイン画面へ
    <a href="{{config('services.link_service_front_shop') . 'login'}}">
        <b>{{config('services.link_service_front_shop') . 'login'}}</b>
    </a>
</p>
@endif
<hr>
<p>
    ※当メールは送信専用メールアドレスから配信されております。 このままご返信いただいてもお答えできませんのでご了承下さい。
</p>
