@component('mail::message')
@component('mail::title')
@lang('API Call Failed')
@endcomponent

@lang('Hi :appellative,', ['appellative' => $appellative])

@component('mail::alert', ['variant' => 'danger'])
@lang('The action :action failed on :url with error code :code.', [
    'action' => $action,
    'url' => $url,
    'code' => $code,
])
@endcomponent

@component('mail::box', ['variant' => 'light'])
**@lang('Reported error')**

{{ $message }}
@endcomponent

@component('mail::quote')
{{ $payload }}
@endcomponent

@isset($triggeredBy)
@component('mail::panel')
@lang('Triggered by user id: :id (:email)', [
    'id' => $triggeredBy['id'],
    'email' => $triggeredBy['email'],
])
@endcomponent
@endisset

@component('mail::signature')
@endcomponent
@endcomponent
