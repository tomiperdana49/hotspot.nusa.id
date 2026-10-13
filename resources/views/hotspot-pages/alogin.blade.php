{{-- Shown by the router right after a successful login (it always serves
     this page; it can't be skipped). With a redirect URL set it is only a
     bare hop to that URL, with no card to read; without one it confirms
     the login and goes on to the status page. --}}
{{-- Two separate views: an @extends inside @if would still render the layout. --}}
@if ($t->redirect_url)
@include('hotspot-pages._alogin-redirect')
@else
@include('hotspot-pages._alogin-connected')
@endif
