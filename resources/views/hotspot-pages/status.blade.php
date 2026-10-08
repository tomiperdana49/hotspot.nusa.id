@extends('hotspot-pages._layout')

@section('head')
$(if refresh-timeout)<meta http-equiv="refresh" content="$(refresh-timeout-secs)">$(endif)
@endsection

@section('content')
<p class="lead">{{ __('app.hotspot_page.hello', [], $lang) }} <strong>$(username)</strong></p>
<dl class="stats">
    <div><dt>{{ __('app.hotspot_page.ip', [], $lang) }}</dt><dd>$(ip)</dd></div>
    <div><dt>{{ __('app.hotspot_page.uptime', [], $lang) }}</dt><dd>$(uptime)</dd></div>
    $(if session-time-left)<div><dt>{{ __('app.hotspot_page.time_left', [], $lang) }}</dt><dd>$(session-time-left)</dd></div>$(endif)
    <div><dt>{{ __('app.hotspot_page.upload', [], $lang) }}</dt><dd>$(bytes-in-nice)</dd></div>
    <div><dt>{{ __('app.hotspot_page.download', [], $lang) }}</dt><dd>$(bytes-out-nice)</dd></div>
</dl>
$(if login-by-mac != 'yes')
<form action="$(link-logout)" name="logout" method="post">
    <input type="hidden" name="erase-cookie" value="true">
    <button type="submit" class="btn">{{ __('app.hotspot_page.logout', [], $lang) }}</button>
</form>
$(endif)
@if ($t->contact)
<p class="contact">{{ __('app.hotspot_page.help', [], $lang) }} {!! $text($t->contact) !!}</p>
@endif
@endsection
