@extends('hotspot-pages._layout')

@section('content')
<p class="lead">{!! $t->welcome_text ? $text($t->welcome_text) : e(__('app.hotspot_page.login_lead', [], $lang)) !!}</p>

$(if error)<div class="alert">$(error)</div>$(endif)

{{-- http-chap: send MD5(chap-id + password + chap-challenge), never the plain password. --}}
$(if chap-id)
<form name="sendin" action="$(link-login-only)" method="post" style="display:none">
    <input type="hidden" name="username">
    <input type="hidden" name="password">
    <input type="hidden" name="dst" value="$(link-orig)">
    <input type="hidden" name="popup" value="true">
</form>
<script src="/md5.js"></script>
<script>
function doLogin() {
    var f = document.login;
    document.sendin.username.value = f.username.value;
    document.sendin.password.value = hexMD5('$(chap-id)' + f.password.value + '$(chap-challenge)');
    document.sendin.submit();
    return false;
}
</script>
$(endif)

<form name="login" action="$(link-login-only)" method="post" $(if chap-id)onsubmit="return doLogin()"$(endif)>
    <input type="hidden" name="dst" value="$(link-orig)">
    <input type="hidden" name="popup" value="true">
@if ($t->login_mode === 'voucher')
    <label for="username">{{ __('app.hotspot_page.voucher', [], $lang) }}</label>
    <input type="text" id="username" name="username" value="$(username)" placeholder="{{ __('app.hotspot_page.voucher_placeholder', [], $lang) }}" autocomplete="off" autocapitalize="off" spellcheck="false" required oninput="this.form.password.value = this.value">
    <input type="hidden" name="password" value="$(username)">
@else
    <label for="username">{{ __('app.hotspot_page.username', [], $lang) }}</label>
    <input type="text" id="username" name="username" value="$(username)" autocomplete="username" autocapitalize="off" spellcheck="false" required>
    <label for="password">{{ __('app.hotspot_page.password', [], $lang) }}</label>
    <div class="pw">
        <input type="password" id="password" name="password" autocomplete="current-password" required>
        {{-- No "$" anywhere in this script: the router reads $(...) as its own variables. --}}
        <button type="button" class="pw-toggle" aria-pressed="false" aria-label="{{ __('app.hotspot_page.show_password', [], $lang) }}"
                data-show="{{ __('app.hotspot_page.show_password', [], $lang) }}" data-hide="{{ __('app.hotspot_page.hide_password', [], $lang) }}"
                onclick="togglePassword(this)">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12z" stroke-linejoin="round"/><circle cx="12" cy="12" r="3"/></svg>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true" hidden><path d="M3 3l18 18" stroke-linecap="round"/><path d="M10.6 5.1A10.6 10.6 0 0112 5c6.4 0 10 7 10 7a17 17 0 01-3.2 4.1M6.6 6.6C3.9 8.4 2 12 2 12s3.6 7 10 7c1.9 0 3.6-.6 5.1-1.5" stroke-linecap="round" stroke-linejoin="round"/><path d="M9.9 9.9a3 3 0 004.2 4.2" stroke-linecap="round"/></svg>
        </button>
    </div>
    <script>
    function togglePassword(button) {
        var input = document.getElementById('password');
        var show = input.type === 'password';
        var icons = button.getElementsByTagName('svg');
        input.type = show ? 'text' : 'password';
        icons[0].hidden = show;
        icons[1].hidden = ! show;
        button.setAttribute('aria-pressed', show ? 'true' : 'false');
        button.setAttribute('aria-label', button.getAttribute(show ? 'data-hide' : 'data-show'));
        input.focus();
    }
    </script>
@endif
    <button type="submit" class="btn">{{ __('app.hotspot_page.login', [], $lang) }}</button>
</form>

@if ($t->contact)
<p class="contact">{{ __('app.hotspot_page.help', [], $lang) }} {!! $text($t->contact) !!}</p>
@endif
@endsection
