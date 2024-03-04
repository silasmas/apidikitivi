<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="X-UA-Compatible" content="ie=edge">
    </head>

    <body>
        <img src="{{ asset('assets/img/logo-text.png') }}" alt="DikiTivi" width="100">

        <p style="margin-top: 3rem; margin-bottom: 1rem;">@lang('notifications.token_label')</p>
        <h1 style="font-weight: 900;">{{ $token }}</h1>
    </body>
</html>