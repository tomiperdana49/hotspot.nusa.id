@extends('hotspot-pages._layout')

@section('content')
<p class="lead">{{ __('app.hotspot_page.logged_out', [], $lang) }}</p>
<dl class="stats">
    <div><dt>{{ __('app.hotspot_page.username', [], $lang) }}</dt><dd>$(username)</dd></div>
    <div><dt>{{ __('app.hotspot_page.uptime', [], $lang) }}</dt><dd>$(uptime)</dd></div>
    <div><dt>{{ __('app.hotspot_page.upload', [], $lang) }}</dt><dd>$(bytes-in-nice)</dd></div>
    <div><dt>{{ __('app.hotspot_page.download', [], $lang) }}</dt><dd>$(bytes-out-nice)</dd></div>
</dl>
<a class="btn" href="$(link-login)">{{ __('app.hotspot_page.login_again', [], $lang) }}</a>
@endsection
