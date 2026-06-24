<x-filament-panels::page>
<div class="oc" style="padding-bottom:40px;max-width:860px">

    {{-- Banner --}}
    <div class="oc-card" style="padding:16px 20px;display:flex;align-items:center;gap:14px;border-color:rgba(167,139,250,.22);background:rgba(167,139,250,.05)">
        <span style="font-size:22px">⚙️</span>
        <div>
            <div style="font-size:13.5px;font-weight:700">Cockpit-Konfiguration</div>
            <div style="font-size:12px;color:var(--oc-text-dim);margin-top:2px">
                Schwellenwerte, Benachrichtigungen &amp; KI-Integration. Nur für Admins.
            </div>
        </div>
    </div>

    <form wire:submit="save">
        {{ $this->form }}
        <div style="display:flex;gap:10px;align-items:center;margin-top:4px">
            <x-filament::button type="submit" color="primary">Einstellungen speichern</x-filament::button>
            <span style="font-size:12px;color:var(--oc-text-faint)">Änderungen wirken sofort.</span>
        </div>
    </form>

</div>
</x-filament-panels::page>
