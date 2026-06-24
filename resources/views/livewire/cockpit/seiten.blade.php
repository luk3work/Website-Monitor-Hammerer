<div>
<div class="topbar">
  <div class="topbar-title">
    <div class="crumb">Betrieb</div>
    <h1>Seiten</h1>
  </div>
  <button class="btn acc"><span class="ti ti-plus"></span>Website verbinden</button>
  <button class="iconbtn"><span class="ti ti-refresh"></span></button>
</div>

<div class="scroll">
<div class="pad">

  <div class="card">
    <div class="sec-h">
      <span class="ti ti-world-www"></span>
      <h3>Alle Websites</h3>
      <span class="cnt">{{ $sites->count() }}</span>
      <div style="margin-left:auto;display:flex;gap:8px;align-items:center">
        <div class="tb-search" style="width:220px;margin-left:0">
          <span class="ti ti-search" style="font-size:14px"></span>
          <input type="text" placeholder="Suchen …" wire:model.live.debounce.250ms="search">
        </div>
        <button class="chip {{ $filter === 'all'      ? 'on' : '' }}" wire:click="setFilter('all')">Alle</button>
        <button class="chip {{ $filter === 'problems' ? 'on' : '' }}" wire:click="setFilter('problems')">Probleme</button>
        <button class="chip {{ $filter === 'offline'  ? 'on' : '' }}" wire:click="setFilter('offline')">Offline</button>
      </div>
    </div>
    <table class="tbl">
      <thead>
        <tr>
          <th>Website</th>
          <th>Status</th>
          <th>SSL</th>
          <th>CMS</th>
          <th>Updates</th>
          <th>Domain läuft ab</th>
          <th>Zustand</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @forelse ($sites as $s)
        @php
          $statusVal = $s->status->value;
          $sslDays   = $s->ssl_expires_at ? (int)$s->ssl_expires_at->diffInDays(now(), false) : null;
          $domDays   = $s->domain_expires_at ? (int)$s->domain_expires_at->diffInDays(now(), false) : null;
          $worst = $statusVal === 'offline' ? 'crit'
                 : (($statusVal === 'maintenance' || ($sslDays !== null && $sslDays <= 30) || $s->pending_updates >= 5) ? 'warn'
                 : 'ok');
        @endphp
        <tr style="cursor:pointer">
          <td>
            <div style="display:flex;align-items:center;gap:11px">
              <span class="dot {{ $statusVal === 'online' ? 'd-ok' : ($statusVal === 'offline' ? 'd-crit' : 'd-warn') }}"></span>
              <div>
                <div style="font-weight:600;font-size:13px">{{ $s->label }}</div>
                <div class="faint" style="font-size:11.5px">{{ $s->customer?->name }}</div>
              </div>
            </div>
          </td>
          <td>
            <span class="badge {{ $statusVal === 'online' ? 'b-ok' : ($statusVal === 'offline' ? 'b-crit' : 'b-warn') }}">
              {{ match($statusVal) { 'online' => 'Online', 'offline' => 'Offline', 'maintenance' => 'Wartung', default => 'Unbekannt' } }}
            </span>
          </td>
          <td style="color:{{ $sslDays === null ? 'var(--faint)' : ($sslDays <= 7 ? 'var(--crit)' : ($sslDays <= 30 ? 'var(--warn)' : 'var(--dim)')) }}">
            {{ $sslDays === null ? '–' : $sslDays.'d' }}
          </td>
          <td class="muted">WP {{ $s->wp_version ?? '–' }}</td>
          <td>
            @if($s->pending_updates > 0)
              <span class="badge b-warn">{{ $s->pending_updates }}</span>
            @else
              <span class="faint">0</span>
            @endif
          </td>
          <td style="color:{{ $domDays === null ? 'var(--faint)' : ($domDays <= 30 ? 'var(--crit)' : ($domDays <= 90 ? 'var(--warn)' : 'var(--dim)')) }}">
            {{ $domDays === null ? '–' : $domDays.'d' }}
          </td>
          <td>
            @if($worst === 'crit')
              <span class="badge b-crit">Kritisch</span>
            @elseif($worst === 'warn')
              <span class="badge b-warn">Warnung</span>
            @else
              <span class="ti ti-circle-check" style="color:var(--ok)"></span>
            @endif
          </td>
          <td>
            <a href="{{ route('cockpit.kunden') }}" class="btn ghost" style="padding:5px 9px;font-size:11.5px">Details</a>
          </td>
        </tr>
        @empty
        <tr><td colspan="8" style="text-align:center;padding:48px;color:var(--faint)">Keine Websites gefunden.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

</div>
</div>
</div>
