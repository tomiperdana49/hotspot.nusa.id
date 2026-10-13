@extends('hotspot-pages._layout')

{{-- Shown by the router right after a successful login. Goes straight on
     to the client's redirect URL when set, otherwise to the status page;
     the button stays as a fallback where scripts and refresh are blocked. --}}
@php $target = $t->redirect_url ? $text($t->redirect_url) : '$(link-status)'; @endphp

@section('head')
<meta http-equiv="refresh" content="0; url={!! $target !!}">
@endsection

@section('content')
<div class="ok">&#10003;</div>
<p class="lead">{{ __('app.hotspot_page.connected', [], $lang) }}</p>
<a class="btn" id="continue" href="{!! $target !!}">{{ __('app.hotspot_page.continue', [], $lang) }}</a>
{{-- Read the address back from the link so entities in it are decoded by the browser. --}}
<script>location.replace(document.getElementById('continue').href);</script>
@endsection
