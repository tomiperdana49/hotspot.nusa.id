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
    <input type="password" id="password" name="password" autocomplete="current-password" required>
@endif
    <button type="submit" class="btn">{{ __('app.hotspot_page.login', [], $lang) }}</button>
</form>

@if ($t->contact)
<p class="contact">{{ __('app.hotspot_page.help', [], $lang) }} {!! $text($t->contact) !!}</p>
@endif
@endsection
