<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * Blade: resources/views/components/flash-messages.blade.php
 */
class FlashMessages extends Component
{
    public function __construct(
        public ?string $flashSuccess = null,
        public ?string $flashError = null,
    ) {
        $this->flashSuccess ??= session('flash.success') ?? session('success');
        $this->flashError ??= session('flash.error') ?? session('error');
    }

    public function render(): View
    {
        return view('components.flash-messages');
    }
}
