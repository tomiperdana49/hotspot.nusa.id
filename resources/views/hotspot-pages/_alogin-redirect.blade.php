{{-- alogin.html when the template has a redirect URL: a bare hop there. --}}
@php $target = $text($t->redirect_url); @endphp
<!DOCTYPE html>
<html lang="{{ $lang }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="refresh" content="0; url={!! $target !!}">
<meta http-equiv="pragma" content="no-cache">
<meta http-equiv="expires" content="-1">
<title>{{ __('app.hotspot_page.redirecting', [], $lang) }}</title>
<style>
body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; font: 14px/1.5 system-ui, -apple-system, "Segoe UI", sans-serif; background: #fff; color: #6b7280; }
a { color: inherit; }
</style>
</head>
<body>
{{-- Read the address back from the link so entities in it are decoded by the browser. --}}
<a id="continue" href="{!! $target !!}">{{ __('app.hotspot_page.redirecting', [], $lang) }}</a>
<script>location.replace(document.getElementById('continue').href);</script>
</body>
</html>
