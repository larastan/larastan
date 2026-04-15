@lang('messages.foo')
@choice('messages.foo')
{{ __('messages.foo') }}
{{ trans('messages.foo') }}
{{ trans_choice('messages.foo', 1) }}
{{ Lang::get('messages.foo') }}
{{ Lang::choice('messages.foo', 1) }}
{{ Lang::trans('messages.foo', 1) }}
{{ Lang::transChoice('messages.foo', 1) }}

@lang('foo bar baz');
@lang('messages');
@lang('messages.nested');
@lang('messages.nested.key');

@lang('sub/lines.greeting');

@lang('vendor::key');

@lang('messages.You\'re beautiful');

@lang('messages.A.Key.With.Dots.');

More regex tests:

{{ Lang::get('lang.get') }}
{{ Lang::choice('lang.choice', 1) }}
{{ Lang::trans('lang.trans') }} {{-- removed in Laravel 6+ --}}
{{ Lang::transChoice('lang.transChoice', 2) }} {{-- removed in Laravel 6+ --}}

{{ trans('a.b') }}
{{ trans('a.b.c') }}
{{ trans('a_b.c-d') }}
{{ trans('a.b', [1,2]) }}
{{ trans('a.b!') }}
{{ trans('a.translate me!') }}
{{ trans('a.über~') }}
{{ trans("app.i\'m") }}
{{ trans('app.i\'m') }}
{{ trans('app.\"ok\"') }}
{{ trans("app.\"ok\"") }}
{{ trans_choice('a.b', 1) }}

@lang('directive.lang')
@choice('directive.choice', 1)

Test:@lang('surrounded.by.text')!
<b>@lang('surrounded.by.html.tags')</b>

{{ TRANS('trans.uppercase') }}
{{ $object->trans('object.instance.method') }}
{{ $t('dollar.t') }}
{{ __('double.underscore.helper.function') }}

<input type="email" placeholder="@lang('lang.in.html.attribute')" value="{{ $email ?? old('email') }}" required autofocus>
<p>@lang('lang.with.extra.paramater', ['name' => config('app.name')])</p>
{{-- @lang('lang.in.blade.comment') --}}
<!-- @lang('lang.in.html.comment') -->
@lang('translation.with.a.'.$variable) @lang('and.a.simple.translation.afterwards')
test@lang('lang.with.prefix')

Should not match:
{{ trans($variableInsteadOfString) }}
{{ trans() }}
{{ differentMethodTrans('different.method.trans') }}
{{ Method1trans('method.1.trans') }}
{{ some_other_method_trans('some.other.method.trans') }}
{{ trans('trans.with.concatenated.string' . $object->property) }}

Keys with special characters:
@lang("lang.with.new\nline.char")
@lang('lang.spanning
multiple.lines')
@lang('lang.with.a.back\\slash.single.quotes')
@lang("lang.with.a.back\\slash.double.quotes")
@lang("lang.with.\x61.hex.char")

@lang("messages.with.new\nline.char")
@lang('messages.spanning
multiple.lines')
@lang('messages.with.a.back\\slash.single.quotes')
@lang("messages.with.a.back\\slash.double.quotes")
@lang("messages.with.\x61.hex.char")
