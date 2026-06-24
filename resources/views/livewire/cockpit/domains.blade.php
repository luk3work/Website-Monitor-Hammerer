<div>
<div class="topbar">
  <div class="topbar-title">
    <div class="crumb">Betrieb</div>
    <h1>Domains &amp; SSL</h1>
  </div>
  <button class="iconbtn"><span class="ti ti-refresh"></span></button>
</div>

<div class="scroll">
<div class="pad" style="display:flex;flex-direction:column;gap:18px">

  {{-- Summary-Chips + Filter --}}
  <div class="dom-chips">
    <div class="dom-chip neutral">
      <span class="num">{{ $summary['total'] }}</span><span class="lbl">Gesamt</span>
    </div>
    <div class="dom-chip crit">
      <span class="num">{{ $summary['critical'] }}</span><span class="lbl">Kritisch</span>
    </div>
    <div class="dom-chip warn">
      <span class="num">{{ $summary['warning'] }}</span><span class="lbl">Bald fällig</span>
    </div>
    <div class="dom-chip ok">
      <span class="num">{{ $summary['ok'] }}</span><span class="lbl">OK</span>
    </div>
    <div style="flex:1"></div>
    <button class="chip {{ $filter === 'all'      ? 'on' : '' }}" wire:click="setFilter('all')">Alle</button>
    <button class="chip {{ $filter === 'critical' ? 'on' : '' }}" wire:click="setFilter('critical')">Kritisch</button>
    <button class="chip {{ $filter === 'warning'  ? 'on' : '' }}" wire:click="setFilter('warning')">Warnung</button>
    <button class="chip {{ $filter === 'ok'       ? 'on' : '' }}" wire:click="setFilter('ok')">OK</button>
    <div class="tb-search" style="margin-left:8px;width:220px">
      <span class="ti ti-search" style="font-size:14px"></span>
      <input type="text" placeholder="Domain suchen …" wire:model.live.debounce.250ms="search">
    </div>
  </div>

  {{-- Tabelle --}}
  <div class="card">
    <div class="sec-h">
      <span class="ti ti-world"></span>
      <h3>Domains &amp; SSL-Zertifikate</h3>
      <span class="cnt">{{ $rows->count() }}</span>
    </div>
    <table class="tbl">
      <thead>
        <tr>
          <th style="width:32px"></th>
          <th>Website</th>
          <th>Kunde</th>
          <th>Host</th>
          <th>SSL-Status</th>
          <th>SSL läuft ab</th>
          <th>Domain läuft ab</th>
          <th>Domain</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse ($rows as $row)
        @php $s = $row['s']; @endphp
        <tr>
          <td>
            <div class="sev {{ $row['worst'] }}" style="width:26px;height:26px;border-radius:7px;font-size:12px">
              @if($row['worst'] === 'crit') ⚠ @elseif($row['worst'] === 'warn') ⚡ @else ✓ @endif
            </div>
          </td>
          <td>
            <div style="font-weight:600;font-size:13px">{{ $s->label }}</div>
            @if($s->wp_version)<div style="font-size:11px;color:var(--faint)">WP {{ $s->wp_version }}</div>@endif
          </td>
          <td class="muted" style="font-size:12.5px">{{ $s->customer?->name ?? '—' }}</td>
          <td>
            <a href="{{ $s->url }}" target="_blank" rel="noopener" style="color:var(--dim);font-size:11.5px;font-family:monospace">
              {{ parse_url($s->url, PHP_URL_HOST) ?? $s->url }}
            </a>
          </td>
          <td>
            <span class="badge {{ $row['sslTone'] === 'crit' ? 'b-crit' : ($row['sslTone'] === 'warn' ? 'b-warn' : ($row['sslTone'] === 'ok' ? 'b-ok' : 'b-neutral')) }}">
              @if($row['sslTone'] === 'crit') ✗ Kritisch
              @elseif($row['sslTone'] === 'warn') ⚡ Bald fällig
              @elseif($row['sslTone'] === 'ok') ✓ Gültig
              @else – Unbekannt @endif
            </span>
          </td>
          <td>
            @if($s->ssl_expires_at)
              <span style="font-weight:700;color:{{ $row['sslTone'] === 'crit' ? 'var(--crit)' : ($row['sslTone'] === 'warn' ? 'var(--warn)' : 'var(--ok)') }}">
                {{ $row['sslDays'] < 0 ? 'Abgelaufen' : $row['sslDays'].'d' }}
              </span>
              <div style="font-size:10.5px;color:var(--faint);margin-top:2px">{{ $s->ssl_expires_at->format('d.m.Y') }}</div>
            @else <span class="faint">–</span> @endif
          </td>
          <td>
            @if($s->domain_expires_at)
              <span style="font-weight:700;color:{{ $row['domTone'] === 'crit' ? 'var(--crit)' : ($row['domTone'] === 'warn' ? 'var(--warn)' : 'var(--ok)') }}">
                {{ $row['domDays'] < 0 ? 'Abgelaufen' : $row['domDays'].'d' }}
              </span>
              <div style="font-size:10.5px;color:var(--faint);margin-top:2px">{{ $s->domain_expires_at->format('d.m.Y') }}</div>
            @else <span class="faint">–</span> @endif
          </td>
          <td>
            <span class="badge {{ $s->domain_by_us ? 'b-neutral' : 'b-info' }}">
              {{ $s->domain_by_us ? 'Bei uns' : 'Extern' }}
            </span>
          </td>
          <td>
            <a href="{{ route('cockpit.kunden') }}" class="btn ghost" style="padding:5px 9px;font-size:11.5px">Details</a>
          </td>
        </tr>
        @empty
        <tr><td colspan="9" style="padding:48px;text-align:center;color:var(--faint)">Keine Domains gefunden.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Legende --}}
  <div class="card" style="padding:16px 20px">
    <div style="display:flex;gap:24px;flex-wrap:wrap;align-items:center">
      <span style="font-size:11px;font-weight:700;color:var(--faint);text-transform:uppercase;letter-spacing:.06em">Legende</span>
      <div style="font-size:12.5px;color:var(--dim)"><span class="badge b-crit" style="margin-right:8px">Kritisch</span>Abgelaufen oder &lt;14d (SSL) / &lt;30d (Domain)</div>
      <div style="font-size:12.5px;color:var(--dim)"><span class="badge b-warn" style="margin-right:8px">Warnung</span>14–45d (SSL) / 30–90d (Domain)</div>
      <div style="font-size:12.5px;color:var(--dim)"><span class="badge b-ok" style="margin-right:8px">OK</span>Mehr als 45d (SSL) / 90d (Domain)</div>
    </div>
  </div>

</div>
</div>
</div>
