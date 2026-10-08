{{--
    Shared shell for the hotspot pages pushed to MikroTik. Everything
    must stay inline (no external CSS/fonts): the device isn't logged in
    yet, so only files in the router's html-directory are reachable.
    $(…) placeholders are MikroTik's, filled in by the router.
--}}
<!DOCTYPE html>
<html lang="{{ $lang }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta http-equiv="pragma" content="no-cache">
<meta http-equiv="expires" content="-1">
<title>{!! $text($t->title) !!}</title>
@yield('head')
<style>
:root { --brand: {{ $brand }}; --brand-dark: {{ $brandDark }}; }
* { box-sizing: border-box; }
body { margin: 0; min-height: 100vh; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 24px 16px; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; font-size: 15px; line-height: 1.5; }
.card { width: 100%; max-width: 380px; border-radius: 20px; padding: 32px 28px; }
.logo { display: block; max-width: 160px; max-height: 72px; margin: 0 auto 16px; }
.mark { width: 56px; height: 56px; margin: 0 auto 16px; border-radius: 16px; background: var(--brand); color: #fff; font-size: 24px; font-weight: 700; display: flex; align-items: center; justify-content: center; }
h1 { font-size: 22px; line-height: 1.25; margin: 0 0 6px; text-align: center; }
.lead { margin: 0 0 24px; text-align: center; opacity: .7; font-size: 14px; }
label { display: block; font-size: 13px; font-weight: 600; margin: 0 0 6px; }
input[type=text], input[type=password] { width: 100%; padding: 12px 14px; border-radius: 12px; border: 1px solid #d1d5db; background: #fff; color: #111827; font-size: 16px; margin: 0 0 14px; outline: none; }
input[type=text]:focus, input[type=password]:focus { border-color: var(--brand); box-shadow: 0 0 0 3px color-mix(in srgb, var(--brand) 20%, transparent); }
.btn { display: block; width: 100%; padding: 13px; margin-top: 6px; border: 0; border-radius: 12px; background: var(--brand); color: #fff; font-size: 16px; font-weight: 600; text-align: center; text-decoration: none; cursor: pointer; }
.btn:hover { background: var(--brand-dark); }
.alert { padding: 10px 12px; border-radius: 10px; background: #fef2f2; color: #b91c1c; font-size: 13px; margin: 0 0 16px; }
.stats { margin: 0 0 20px; padding: 0; border-radius: 12px; border: 1px solid rgba(127, 127, 127, .2); }
.stats div { display: flex; justify-content: space-between; gap: 12px; padding: 10px 14px; font-size: 14px; }
.stats div + div { border-top: 1px solid rgba(127, 127, 127, .2); }
.stats dt { opacity: .65; }
.stats dd { margin: 0; font-weight: 600; text-align: right; word-break: break-all; }
.ok { width: 56px; height: 56px; margin: 0 auto 16px; border-radius: 50%; background: var(--brand); color: #fff; font-size: 28px; display: flex; align-items: center; justify-content: center; }
.contact { margin: 20px 0 0; text-align: center; font-size: 13px; opacity: .7; }
.footer { margin: 20px 0 0; max-width: 380px; text-align: center; font-size: 12px; opacity: .8; }
.theme-modern { background: linear-gradient(140deg, var(--brand), var(--brand-dark)); color: #111827; }
.theme-modern .card { background: #fff; box-shadow: 0 20px 50px rgba(0, 0, 0, .25); }
.theme-modern .footer { color: #fff; }
.theme-minimal { background: #f3f4f6; color: #111827; }
.theme-minimal .card { background: #fff; border-top: 5px solid var(--brand); box-shadow: 0 4px 20px rgba(0, 0, 0, .06); }
.theme-dark { background: #0b1120; color: #e5e7eb; }
.theme-dark .card { background: #111827; border: 1px solid #1f2937; }
.theme-dark input[type=text], .theme-dark input[type=password] { background: #0b1120; border-color: #374151; color: #f9fafb; }
.theme-dark .alert { background: #3f1d1d; color: #fecaca; }
@if ($t->backgroundFileName())
body.has-bg { background: linear-gradient(rgba(0, 0, 0, .35), rgba(0, 0, 0, .35)), url("{{ $t->backgroundFileName() }}") center / cover no-repeat fixed; }
body.has-bg .footer { color: #fff; text-shadow: 0 1px 3px rgba(0, 0, 0, .6); }
@endif
</style>
</head>
<body class="theme-{{ $t->theme }}{{ $t->backgroundFileName() ? ' has-bg' : '' }}">
<div class="card">
    @if ($t->logoFileName())
        <img class="logo" src="{{ $t->logoFileName() }}" alt="">
    @else
        <div class="mark">{!! $text(mb_strtoupper(mb_substr($t->title, 0, 1))) !!}</div>
    @endif
    <h1>{!! $text($t->title) !!}</h1>
    @yield('content')
</div>
@if ($t->footer_text)
    <p class="footer">{!! $text($t->footer_text) !!}</p>
@endif
</body>
</html>
