<div>
<div class="topbar">
  <div class="topbar-title">
    <div class="crumb">System</div>
    <h1>Einstellungen</h1>
  </div>
</div>

<div class="scroll">
<div class="pad" style="max-width:780px;display:flex;flex-direction:column;gap:20px">

  @if(session('saved'))
  <div style="padding:12px 18px;border-radius:var(--r3);background:rgba(52,211,153,.12);border:1px solid rgba(52,211,153,.25);color:#6ee7b7;font-size:13px;font-weight:600;display:flex;align-items:center;gap:9px">
    <span class="ti ti-circle-check"></span>Einstellungen gespeichert.
  </div>
  @endif

  {{-- Info-Banner --}}
  <div class="card" style="padding:16px 20px;display:flex;align-items:center;gap:14px;border-color:rgba(167,139,250,.22);background:rgba(167,139,250,.05)">
    <span style="font-size:22px">⚙️</span>
    <div>
      <div style="font-size:13.5px;font-weight:700">Cockpit-Konfiguration</div>
      <div style="font-size:12px;color:var(--dim);margin-top:2px">Schwellenwerte, Benachrichtigungen &amp; KI-Integration. Nur für Admins.</div>
    </div>
  </div>

  <form wire:submit.prevent="save" style="display:flex;flex-direction:column;gap:18px">

    {{-- Schwellenwerte --}}
    <div class="card">
      <div class="sec-h"><span class="ti ti-adjustments"></span><h3>Schwellenwerte &amp; Monitoring</h3></div>
      <div style="padding:20px;display:grid;grid-template-columns:1fr 1fr;gap:18px">
        <div>
          <label style="font-size:12px;color:var(--dim);font-weight:600;display:block;margin-bottom:8px">
            SSL-Warnung ab (Tage)
          </label>
          <input type="number" wire:model="sslWarnDays" min="1" max="180"
            style="background:var(--panel2);border:1px solid var(--line2);border-radius:var(--r3);color:var(--tx);padding:9px 12px;font-size:13px;width:100%;outline:none;font-family:inherit">
          <div style="font-size:11px;color:var(--faint);margin-top:5px">SSL-Zertifikate mit weniger Tagen werden markiert.</div>
        </div>
        <div>
          <label style="font-size:12px;color:var(--dim);font-weight:600;display:block;margin-bottom:8px">
            Domain-Warnung ab (Tage)
          </label>
          <input type="number" wire:model="domainWarnDays" min="1" max="365"
            style="background:var(--panel2);border:1px solid var(--line2);border-radius:var(--r3);color:var(--tx);padding:9px 12px;font-size:13px;width:100%;outline:none;font-family:inherit">
          <div style="font-size:11px;color:var(--faint);margin-top:5px">Domains mit weniger Tagen werden gewarnt.</div>
        </div>
        <div>
          <label style="font-size:12px;color:var(--dim);font-weight:600;display:block;margin-bottom:8px">
            Heartbeat-Timeout (Stunden)
          </label>
          <input type="number" wire:model="heartbeatHours" min="1" max="168"
            style="background:var(--panel2);border:1px solid var(--line2);border-radius:var(--r3);color:var(--tx);padding:9px 12px;font-size:13px;width:100%;outline:none;font-family:inherit">
          <div style="font-size:11px;color:var(--faint);margin-top:5px">Nach dieser Zeit ohne Reporter-Signal → Site als offline markiert.</div>
        </div>
      </div>
    </div>

    {{-- Benachrichtigungen --}}
    <div class="card">
      <div class="sec-h"><span class="ti ti-bell"></span><h3>Benachrichtigungen</h3></div>
      <div style="padding:20px">
        <label style="display:flex;align-items:center;gap:14px;cursor:pointer">
          <div style="position:relative">
            <input type="checkbox" wire:model="emailAlerts" style="width:36px;height:20px;appearance:none;background:{{ $emailAlerts ? 'var(--acc)' : 'var(--panel2)' }};border:1px solid {{ $emailAlerts ? 'var(--acc)' : 'var(--line2)' }};border-radius:99px;cursor:pointer;transition:.2s">
          </div>
          <div>
            <div style="font-size:13.5px;font-weight:600">E-Mail-Alerts aktivieren</div>
            <div style="font-size:12px;color:var(--dim);margin-top:2px">Kritische Ereignisse per E-Mail benachrichtigen.</div>
          </div>
        </label>
      </div>
    </div>

    {{-- KI-Integration --}}
    <div class="card">
      <div class="sec-h"><span class="ti ti-robot"></span><h3>KI-Integration</h3></div>
      <div style="padding:20px">
        <label style="font-size:12px;color:var(--dim);font-weight:600;display:block;margin-bottom:8px">KI-Provider</label>
        <select wire:model="aiProvider"
          style="background:var(--panel2);border:1px solid var(--line2);border-radius:var(--r3);color:var(--tx);padding:9px 12px;font-size:13px;width:260px;outline:none;font-family:inherit">
          <option value="none">Kein KI-Provider (regelbasiert)</option>
          <option value="anthropic">Anthropic Claude (EU)</option>
          <option value="openai">OpenAI GPT</option>
        </select>
        <div style="font-size:11.5px;color:var(--faint);margin-top:8px;max-width:480px">
          KI-Triage analysiert Snapshots und priorisiert Handlungsempfehlungen.
          Kein Provider = regelbasierte Auswertung ohne externe API-Aufrufe.
        </div>
      </div>
    </div>

    <div>
      <button type="submit" class="btn acc">
        <span class="ti ti-device-floppy"></span>Einstellungen speichern
      </button>
    </div>

  </form>

</div>
</div>
</div>
