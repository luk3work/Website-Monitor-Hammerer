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
        // Setting::value ist als array gecastet. Werte können als {"v": …}
        // (Setting::set) oder als reiner Skalar abgelegt sein – beides robust lesen.
        $this->sslWarnDays    = (int) $this->readSetting('ssl_warn_days', 30);
        $this->domainWarnDays = (int) $this->readSetting('domain_warn_days', 60);
        $this->heartbeatHours = (int) $this->readSetting('heartbeat_hours', 26);
        $this->aiProvider     = (string) ($this->readSetting('ai_provider', 'none') ?: 'none');
        $this->emailAlerts    = (bool) $this->readSetting('email_alerts', false);
    }

    /** Liest einen Skalar aus der settings-Tabelle, egal ob {"v":…} oder roh. */
    private function readSetting(string $key, mixed $default): mixed
    {
        $raw = Setting::query()->where('key', $key)->value('value');

        if (is_array($raw)) {
            return $raw['v'] ?? $default;
        }

        return $raw ?? $default;
    }

    public function save(): void
    {
        // Einheitlich im {"v": …}-Format ablegen (Setting::set / Setting::get).
        Setting::set('ssl_warn_days', $this->sslWarnDays);
        Setting::set('domain_warn_days', $this->domainWarnDays);
        Setting::set('heartbeat_hours', $this->heartbeatHours);
        Setting::set('ai_provider', $this->aiProvider);
        Setting::set('email_alerts', $this->emailAlerts);

        session()->flash('saved', true);
    }

    public function render()
    {
        return view('livewire.cockpit.einstellungen');
    }
}
