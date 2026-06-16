<?php

namespace App\View\Components;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class NavLink extends Component
{
    public bool $isActive;

    public string $url;

    public function __construct(
        public ?string $routeName = null,
        public ?string $href = null,
        public string $label = '',
        public bool $external = false,
        public bool $sidebarCollapsed = false,
    ) {
        $this->isActive = $routeName && request()->routeIs($routeName);
        $this->url = $routeName ? route($routeName) : ($href ?? '#');
    }

    public function render(): View
    {
        return view('components.nav-link');
    }
}
