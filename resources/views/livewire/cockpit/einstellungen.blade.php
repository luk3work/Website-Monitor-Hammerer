<div>
<div class="topbar">
  <div class="topbar-left">
    <span class="crumb">System</span>
    <span class="crumb-sep">/</span>
    <h1>Einstellungen</h1>
  </div>
</div>
<div class="scroll">
<div class="pad" style="max-width:760px;display:flex;flex-direction:column;gap:18px">

  @if(session('saved'))
  <div class="flash-ok"><span class="ti ti-circle-check"></span>Einstellungen gespeichert.</div>
  @endif

  <form wire:submit.prevent="save" style="display:flex;flex-direction:column;gap:16px">

    <div class="card">
      <div class="sec-h"><span class="ti ti-adjustments"></span><h3>Schwellenwerte & Monitoring</h3></div>
      <div style="padding:20px;display:grid;grid-template-columns:1fr 1fr;gap:16px">
        <div>
          <label class="lbl" for="ssl-days">SSL-Warnung ab (Tage)</label>
          <input id="ssl-days" class="field" type="number" wire:model="sslWarnDays" min="1" max="180">
          <div style="font-size:11.5px;color:var(--faint);margin-top:5px">SSL unter diesem Wert → Warning.</div>
        </div>
        <div>
          <label class="lbl" for="dom-days">Domain-Warnung ab (Tage)</label>
          <input id="dom-days" class="field" type="number" wire:model="domainWarnDays" min="1" max="365">
          <div style="font-size:11.5px;color:var(--faint);margin-top:5px">Domain unter diesem Wert → Warning.</div>
        </div>
        <div>
          <label class="lbl" for="hb-hours">Heartbeat-Timeout (Stunden)</label>
          <input id="hb-hours" class="field" type="number" wire:model="heartbeatHours" min="1" max="168">
          <div style="font-size:11.5px;color:var(--faint);margin-top:5px">Kein Signal → Site als offline markiert.</div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="sec-h"><span class="ti ti-bell"></span><h3>Benachrichtigungen</h3></div>
      <div style="padding:20px">
        <label style="display:flex;align-items:center;gap:14px;cursor:pointer">
          <input class="toggle" type="checkbox" wire:model="emailAlerts">
          <div>
            <div style="font-weight:600;font-size:13.5px">E-Mail-Alerts aktivieren</div>
            <div style="font-size:12px;color:var(--dim);margin-top:2px">Kritische Ereignisse per E-Mail.</div>
          </div>
        </label>
      </div>
    </div>

    <div class="card">
      <div class="sec-h"><span class="ti ti-robot"></span><h3>KI-Integration</h3></div>
      <div style="padding:20px">
        <label class="lbl">KI-Provider</label>
        <select class="field" wire:model="aiProvider" style="max-width:300px">
          <option value="none">Kein KI-Provider (regelbasiert)</option>
          <option value="anthropic">Anthropic Claude</option>
          <option value="openai">OpenAI GPT</option>
        </select>
        <div style="font-size:11.5px;color:var(--faint);margin-top:8px;max-width:480px">KI-Triage analysiert Snapshots und priorisiert Handlungsempfehlungen.</div>
      </div>
    </div>

    <div>
      <button type="submit" class="btn acc"><span class="ti ti-device-floppy"></span>Einstellungen speichern</button>
    </div>

  </form>
</div>
</div>
</div>
