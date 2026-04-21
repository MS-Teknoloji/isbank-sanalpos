<!doctype html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0">
    <meta http-equiv="Content-Language" content="tr">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="now">
    <title>{{ __('Isbank Sanalpos — 3D Secure') }}</title>
    <style>
        body { font-family: Verdana, Geneva, Tahoma, sans-serif; text-align: center; padding: 50px; margin: 0; background: #f6f7f9; }
        .card { max-width: 520px; margin: 40px auto; background: #fff; border-radius: 8px;
                padding: 40px 30px; box-shadow: 0 2px 16px rgba(0,0,0,0.08); }
        .loader { border: 6px solid #f3f3f3; border-top: 6px solid #004990; border-radius: 50%;
                  width: 50px; height: 50px; animation: spin 1s linear infinite; margin: 20px auto; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        h3 { color: #004990; }
        p { color: #555; font-size: 14px; }
        button { background: #004990; color: #fff; border: 0; padding: 12px 28px;
                 font-size: 15px; border-radius: 4px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="card">
        <h3>{{ __('Isbank 3D Secure sayfasına yönlendiriliyorsunuz...') }}</h3>
        <div class="loader"></div>
        <p>{{ __('Lütfen bu sayfayı kapatmayınız.') }}</p>

        <form id="isbank-form" method="post" action="{{ $gatewayUrl }}">
            @foreach ($formData as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
            <noscript>
                <button type="submit">{{ __('Devam Et') }}</button>
            </noscript>
        </form>

        <script>
            document.getElementById('isbank-form').submit();
        </script>
    </div>
</body>
</html>
