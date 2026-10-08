@extends('hotspot-pages._layout')

{{-- Shown by the router right after a successful login. Goes to the
     client's redirect URL when set, otherwise to the status page. --}}
@php $target = $t->redirect_url ? $text($t->redirect_url) : '$(link-status)'; @endphp

@section('head')
<meta http-equiv="refresh" content="2; url={!! $target !!}">
@endsection

@section('content')
<div class="ok">&#10003;</div>
<p class="lead">{{ __('app.hotspot_page.connected', [], $lang) }}</p>
<a class="btn" href="{!! $target !!}">{{ __('app.hotspot_page.continue', [], $lang) }}</a>
@endsection
