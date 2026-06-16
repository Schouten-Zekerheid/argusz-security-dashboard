<?php

namespace App\Livewire\Admin;

use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class SlaSettings extends Component
{
    // Properties must be public for Livewire data binding (wire:model)
    public int $critical;

    public int $high;

    public int $medium;

    public int $low;

    public function mount(): void
    {
        $this->authorize('settings.manage');

        $this->critical = (int) Setting::get('sla_critical_days', config('sla.critical'));
        $this->high = (int) Setting::get('sla_high_days', config('sla.high'));
        $this->medium = (int) Setting::get('sla_medium_days', config('sla.medium'));
        $this->low = (int) Setting::get('sla_low_days', config('sla.low'));
    }

    protected function rules(): array
    {
        return [
            'critical' => ['required', 'integer', 'min:1'],
            'high' => ['required', 'integer', 'different:critical', 'min:'.($this->critical + 1)],
            'medium' => ['required', 'integer', 'different:critical', 'different:high', 'min:'.($this->high + 1)],
            'low' => ['required', 'integer', 'different:critical', 'different:high', 'different:medium', 'min:'.($this->medium + 1)],
        ];
    }

    protected function messages(): array
    {
        return [
            'critical.required' => 'Critical SLA is required.',
            'critical.integer' => 'Critical SLA must be an integer.',
            'critical.min' => 'Critical SLA must be at least 1 day.',
            'high.required' => 'High SLA is required.',
            'high.integer' => 'High SLA must be an integer.',
            'high.different' => 'High SLA cannot be the same as Critical SLA.',
            'high.min' => 'High SLA must be greater than Critical SLA ('.$this->critical.' '.($this->critical === 1 ? 'day' : 'days').').',
            'medium.required' => 'Medium SLA is required.',
            'medium.integer' => 'Medium SLA must be an integer.',
            'medium.different' => 'Medium SLA cannot be the same as another SLA term.',
            'medium.min' => 'Medium SLA must be greater than High SLA ('.$this->high.' '.($this->high === 1 ? 'day' : 'days').').',
            'low.required' => 'Low SLA is required.',
            'low.integer' => 'Low SLA must be an integer.',
            'low.different' => 'Low SLA cannot be the same as another SLA term.',
            'low.min' => 'Low SLA must be greater than Medium SLA ('.$this->medium.' '.($this->medium === 1 ? 'day' : 'days').').',
        ];
    }

    public function saveSettings(): void
    {
        $this->authorize('settings.manage');
        $this->validate();

        $oldValues = [
            'critical' => (int) Setting::get('sla_critical_days', config('sla.critical')),
            'high' => (int) Setting::get('sla_high_days', config('sla.high')),
            'medium' => (int) Setting::get('sla_medium_days', config('sla.medium')),
            'low' => (int) Setting::get('sla_low_days', config('sla.low')),
        ];

        $newValues = [
            'critical' => $this->critical,
            'high' => $this->high,
            'medium' => $this->medium,
            'low' => $this->low,
        ];

        // Save to database
        Setting::set('sla_critical_days', $this->critical);
        Setting::set('sla_high_days', $this->high);
        Setting::set('sla_medium_days', $this->medium);
        Setting::set('sla_low_days', $this->low);

        // Invalidate settings cache
        Cache::forget('sla_settings');

        // Dynamically override active config repository state for immediate request context
        config([
            'sla.critical' => $this->critical,
            'sla.high' => $this->high,
            'sla.medium' => $this->medium,
            'sla.low' => $this->low,
        ]);

        // Audit Logging
        activity()
            ->useLog('settings')
            ->causedBy(auth()->user())
            ->event('sla_settings_updated')
            ->withProperties([
                'old' => $oldValues,
                'new' => $newValues,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('SLA target terms updated by '.auth()->user()->email);

        session()->flash('flash.success', 'SLA terms successfully updated.');
    }

    public function render(): View
    {
        return view('livewire.admin.sla-settings')
            ->title('SLA Settings');
    }
}
