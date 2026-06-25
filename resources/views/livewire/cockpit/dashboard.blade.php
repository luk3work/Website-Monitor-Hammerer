<div>
{{-- Topbar --}}
<div class="topbar">
  <div class="topbar-left">
    <span class="crumb">Ops Cockpit</span>
    <span class="crumb-sep">/</span>
    <h1>Dashboard</h1>
  </div>
  <div class="topbar-actions">
    <a href="{{ route('cockpit.tasks') }}" class="btn ghost sm"><span class="ti ti-plus"></span>Aufgabe</a>
  </div>
</div>

<div class="scroll">
<div class="pad" style="display:flex;flex-direction:column;gap:20px">

  @if(session('created'))
  <div class="flash-ok"><span class="ti ti-circle-check"></span>{{ session('created') }}</div>
  @endif

  {{-- KPI Grid --}}
  <div class="kpi-grid">
    <div class="kpi-card k-acc">
      <div class="kpi-top"><span class="kpi-label">Websites gesamt</span><span class="ti ti-world kpi-icon"></span></div>
      <div class="kpi-value">{{ $totalSites }}</div>
      <div class="kpi-sub"><span class="dot d-ok"></span>{{ $totalSites - $offlineSites }} online</div>
    </div>
    <div class="kpi-card {{ $offlineSites > 0 ? 'k-crit' : 'k-ok' }}">
      <div class="kpi-top"><span class="kpi-label">Offline</span><span class="ti ti-wifi-off kpi-icon"></span></div>
      <div class="kpi-value" style="{{ $offlineSites > 0 ? 'color:var(--crit)' : '' }}">{{ $offlineSites }}</div>
      <div class="kpi-sub {{ $offlineSites > 0 ? 'crit' : 'ok' }}">
        {{ $offlineSites > 0 ? 'Sofort handeln' : 'Alle Sites erreichbar' }}
      </div>
    </div>
    <div class="kpi-card {{ $sslCrit > 0 ? 'k-crit' : ($sslWarn > 0 ? 'k-warn' : 'k-ok') }}">
      <div class="kpi-top"><span class="kpi-label">SSL-Probleme</span><span class="ti ti-certificate kpi-icon"></span></div>
      <div class="kpi-value" style="{{ $sslCrit > 0 ? 'color:var(--crit)' : ($sslWarn > 0 ? 'color:var(--warn)' : '') }}">{{ $sslCrit + $sslWarn }}</div>
      <div class="kpi-sub {{ $sslCrit > 0 ? 'crit' : ($sslWarn > 0 ? 'warn' : 'ok') }}">
        @if($sslCrit > 0) {{ $sslCrit }} kritisch @elseif($sslWarn > 0) {{ $sslWarn }} bald ablaufend @else Alle SSL ok @endif
      </div>
    </div>
    <div class="kpi-card {{ $critTasks > 0 ? 'k-crit' : ($openTasks > 0 ? 'k-warn' : 'k-ok') }}">
      <div class="kpi-top"><span class="kpi-label">Offene Aufgaben</span><span class="ti ti-checklist kpi-icon"></span></div>
      <div class="kpi-value">{{ $openTasks }}</div>
      <div class="kpi-sub {{ $critTasks > 0 ? 'crit' : ($openTasks > 0 ? 'warn' : 'ok') }}">
        @if($critTasks > 0) {{ $critTasks }} kritisch @elseif($openTasks > 0) Handlungsbedarf @else Nichts offen @endif
      </div>
    </div>
    <div class="kpi-card {{ $pendingUpdates >= 10 ? 'k-warn' : 'k-acc' }}">
      <div class="kpi-top"><span class="kpi-label">Ausstehende Updates</span><span class="ti ti-refresh-alert kpi-icon"></span></div>
      <div class="kpi-value">{{ $pendingUpdates }}</div>
      <div class="kpi-sub {{ $pendingUpdates >= 10 ? 'warn' : '' }}">über {{ $totalSites }} Sites</div>
    </div>
    <div class="kpi-card {{ $domCrit > 0 ? 'k-crit' : 'k-ok' }}">
      <div class="kpi-top"><span class="kpi-label">Domain-Probleme</span><span class="ti ti-world-off kpi-icon"></span></div>
      <div class="kpi-value" style="{{ $domCrit > 0 ? 'color:var(--crit)' : '' }}">{{ $domCrit }}</div>
      <div class="kpi-sub {{ $domCrit > 0 ? 'crit' : 'ok' }}">{{ $domCrit > 0 ? 'Ablauf in < 30 Tagen' : 'Alle Domains ok' }}</div>
    </div>
    <div class="kpi-card k-acc">
      <div class="kpi-top"><span class="kpi-label">Kunden</span><span class="ti ti-building-store kpi-icon"></span></div>
      <div class="kpi-value">{{ $totalCustomers }}</div>
      <div class="kpi-sub">aktive Kunden</div>
    </div>
    <div class="kpi-card k-ok">
      <div class="kpi-top"><span class="kpi-label">Letzter Scan</span><span class="ti ti-clock kpi-icon"></span></div>
      <div class="kpi-value" style="font-size:18px;font-weight:700">{{ now()->format('H:i') }}</div>
      <div class="kpi-sub ok">Heute, {{ now()->format('d.m.Y') }}</div>
    </div>
  </div>

  {{-- Problem Feed + Expiries --}}
  <div style="display:grid;grid-template-columns:1fr 340px;gap:16px;align-items:start">

    {{-- Prioritäts-Feed --}}
    <div class="card">
      <div class="sec-h">
        <span class="ti ti-alert-triangle"></span>
        <h3>Handlungsbedarf</h3>
        @if($feed->count() > 0)
          <span class="cnt">{{ $feed->count() }}</span>
        @endif
        <a href="{{ route('cockpit.tasks') }}" class="btn ghost sm ml-auto">Alle Aufgaben</a>
      </div>

      @forelse($feed as $item)
      @php
        $sevClass = match($item['severity']) { 'critical'=>'crit','warning'=>'warn',default=>'info' };
        $dotClass = match($item['severity']) { 'critical'=>'d-crit','warning'=>'d-warn',default=>'d-info' };
      @endphp
      <div class="prow">
        <span class="ti {{ $item['icon'] }} prow-icon text-{{ $sevClass }}"></span>
        <div class="prow-body">
          <div class="prow-title">{{ $item['title'] }}</div>
          <div class="prow-meta">
            @if($item['customer'])
              <span>{{ $item['customer'] }}</span>
              <span class="sep">·</span>
            @endif
            <span>{{ $item['meta'] }}</span>
            <span class="sep">·</span>
            <span class="badge badge-{{ $sevClass }}" style="font-size:10px">
              {{ match($item['severity']) { 'critical'=>'Sofort','warning'=>'Bald',default=>'Info' } }}
            </span>
          </div>
        </div>
        <div class="prow-actions">
          <a href="{{ route('cockpit.tasks', ['newSiteId' => $item['site_id']]) }}"
             class="btn ghost sm"><span class="ti ti-plus"></span>Task</a>
        </div>
      </div>
      @empty
      <div class="empty">
        <span class="ti ti-circle-check" style="color:var(--ok)"></span>
        <h3>Alles in Ordnung</h3>
        <p>Kein aktueller Handlungsbedarf. Alle Sites laufen normal.</p>
      </div>
      @endforelse
    </div>

    {{-- Upcoming Expirations --}}
    <div class="card">
      <div class="sec-h">
        <span class="ti ti-calendar-event"></span>
        <h3>Anstehende Abläufe</h3>
      </div>
      @forelse($expiries as $s)
      @php
        $ssl = $s->sslDaysLeft();
        $dom = $s->domainDaysLeft();
        $days = min($ssl ?? 9999, $dom ?? 9999);
        $cls = $days < 14 ? 'crit' : ($days < 30 ? 'warn' : 'ok');
      @endphp
      <div class="prow" style="padding:10px 16px">
        <span class="dot d-{{ $cls }}" style="margin-top:5px"></span>
        <div class="prow-body">
          <div style="font-size:13px;font-weight:600">{{ $s->name }}</div>
          <div style="font-size:11.5px;color:var(--dim);margin-top:2px">
            @if($ssl !== null && $ssl < 90) SSL: <span class="days-{{ $cls }}">{{ $ssl }}d</span>&ensp; @endif
            @if($dom !== null && $dom < 90) Domain: <span class="days-{{ $cls }}">{{ $dom }}d</span> @endif
          </div>
        </div>
      </div>
      @empty
      <div class="empty" style="padding:32px 16px">
        <span class="ti ti-calendar-check" style="color:var(--ok)"></span>
        <p style="font-size:12px">Keine Abläufe in den nächsten 90 Tagen.</p>
      </div>
      @endforelse
    </div>

  </div>

</div>
</div>
</div>
