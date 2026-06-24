<?php

namespace App\Livewire\Cockpit;

use App\Models\Setting;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.cockpit')]
class Einstellungen extends Component
{
    public int $sslWarnDays    = 30;
    public int $domainWarnDays = 60;
    public int $heartbeatHours = 26;
    public string $aiProvider  = 'none';
    public bool $emailAlerts   = false;

    public function mount(): void
    {
        $this->sslWarnDays    = (int)(Setting::query()->where('key', 'ssl_warn_days')->value('value')    ?? 30);
        $this->domainWarnDays = (int)(Setting::query()->where('key', 'domain_warn_days')->value('value') ?? 60);
        $this->heartbeatHours = (int)(Setting::query()->where('key', 'heartbeat_hours')->value('value')  ?? 26);
        $this->aiProvider     = Setting::query()->where('key', 'ai_provider')->value('value')            ?? 'none';
        $this->emailAlerts    = (bool)(Setting::query()->where('key', 'email_alerts')->value('value')    ?? false);
    }

    public function save(): void
    {
        foreach ([
            'ssl_warn_days'    => $this->sslWarnDays,
            'domain_warn_days' => $this->domainWarnDays,
            'heartbeat_hours'  => $this->heartbeatHours,
            'ai_provider'      => $this->aiProvider,
            'email_alerts'     => (int)$this->emailAlerts,
        ] as $key => $value) {
            Setting::query()->updateOrCreate(['key' => $key], ['value' => (string)$value]);
        }
        session()->flash('saved', true);
    }

    public function render()
    {
        return view('livewire.cockpit.einstellungen');
    }
}
