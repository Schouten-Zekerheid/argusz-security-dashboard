{{--
    $flashSuccess, $flashError: App\View\Components\FlashMessages constructor args;
    when omitted, values default from session keys flash.success / success and flash.error / error.
--}}
@if ($flashSuccess)
    <div class="rounded-lg border border-green-500/30 bg-green-500/10 px-4 py-3 text-sm text-green-300">
        {{ $flashSuccess }}
    </div>
@endif

@if ($flashError)
    <div class="rounded-lg border border-red-500/30 bg-red-500/10 px-4 py-3 text-sm text-red-300">
        {{ $flashError }}
    </div>
@endif
