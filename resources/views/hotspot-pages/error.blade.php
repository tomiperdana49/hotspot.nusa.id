@extends('hotspot-pages._layout')

@section('content')
<div class="alert">$(error)</div>
<a class="btn" href="$(link-login)">{{ __('app.hotspot_page.back', [], $lang) }}</a>
@endsection
