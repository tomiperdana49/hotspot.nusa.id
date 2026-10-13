{{-- alogin.html without a redirect URL: confirm, then go to the status page. --}}
@extends('hotspot-pages._layout')

@section('head')
<meta http-equiv="refresh" content="0; url=$(link-status)">
@endsection

@section('content')
<div class="ok">&#10003;</div>
<p class="lead">{{ __('app.hotspot_page.connected', [], $lang) }}</p>
<a class="btn" id="continue" href="$(link-status)">{{ __('app.hotspot_page.continue', [], $lang) }}</a>
<script>location.replace(document.getElementById('continue').href);</script>
@endsection
