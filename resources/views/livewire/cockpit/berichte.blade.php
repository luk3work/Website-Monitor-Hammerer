<div>
<div class="topbar">
  <div class="topbar-title">
    <div class="crumb">Verwaltung</div>
    <h1>Berichte</h1>
  </div>
  <button class="btn acc"><span class="ti ti-plus"></span>Report erstellen</button>
  <button class="iconbtn"><span class="ti ti-refresh"></span></button>
</div>

<div class="scroll">
<div class="pad" style="display:flex;flex-direction:column;gap:24px">

  {{-- KPIs --}}
  <div class="kpis" style="grid-template-columns:repeat(4,1fr)">
    <div class="card kpi">
      <div class="lab"><span class="ti ti-users"></span>Kunden</div>
      <div class="val">{{ $customers->count() }}</div>
      <span class="badge b-neutral">gesamt betreut</span>
    </div>
    <div class="card kpi">
      <div class="lab"><span class="ti ti-world-www"></span>Websites</div>
      <div class="val">{{ $totalSites }}</div>
      <span class="badge b-neutral">überwacht</span>
    </div>
    <div class="card kpi {{ $pendingUpdates > 0 ? 'edge-warn' : '' }}">
      <div class="lab"><span class="ti ti-refresh"></span>Offene Updates</div>
      <div class="val">{{ $pendingUpdates }}</div>
      <span class="badge {{ $pendingUpdates > 0 ? 'b-warn' : 'b-ok' }}">Plugins &amp; Themes</span>
    </div>
    <div class="card kpi {{ $sslAlerts > 0 ? 'edge-crit' : '' }}">
      <div class="lab"><span class="ti ti-lock"></span>SSL-Alerts</div>
      <div class="val">{{ $sslAlerts }}</div>
      <span class="badge {{ $sslAlerts > 0 ? 'b-crit' : 'b-ok' }}">≤ 30 Tage</span>
    </div>
  </div>

  {{-- Kunden-Reports --}}
  <div>
    <div style="font-size:13px;font-weight:700;color:var(--dim);text-transform:uppercase;letter-spacing:.06em;margin-bottom:14px">
      Monatsreports · {{ now()->format('F Y') }}
    </div>
    @php $pal = ['#d7263d','#0ea5e9','#10b981','#a855f7','#f59e0b','#14b8a6','#ef4444']; @endphp
    <div class="rep-cards">
      @foreach($customers as $c)
      <div class="rep-card">
        <div class="rc-head">
          <div class="rc-logo" style="background:{{ $pal[$c->id % count($pal)] }}">{{ mb_substr($c->name,0,1) }}</div>
          <div>
            <div class="rc-name">{{ $c->name }}</div>
            <div class="rc-sub">{{ now()->format('F Y') }}</div>
          </div>
        </div>
        <div class="rc-badges">
          <span class="badge b-ok">{{ $c->sites->count() }} Sites</span>
          @php $upd = $c->sites->sum('pending_updates'); @endphp
          @if($upd > 0)
            <span class="badge b-warn">{{ $upd }} Updates</span>
          @else
            <span class="badge b-neutral">Alles aktuell</span>
          @endif
        </div>
        <div class="rc-actions">
          <button class="btn ghost" style="flex:1"><span class="ti ti-eye"></span>Ansehen</button>
          <button class="btn acc" style="flex:1"><span class="ti ti-file-type-pdf"></span>PDF</button>
        </div>
      </div>
      @endforeach
    </div>
  </div>

  {{-- Update-Rückstand --}}
  <div class="card">
    <div class="sec-h">
      <span class="ti ti-refresh"></span>
      <h3>Update-Rückstand nach Kunde</h3>
    </div>
    @foreach($customers as $c)
    @php $upd = $c->sites->sum('pending_updates'); $max = max(1,$customers->max(fn($x) => $x->sites->sum('pending_updates'))); @endphp
    <div class="stat-bar-row">
      <div class="stat-bar-label" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $c->name }}</div>
      <div class="stat-bar-track">
        <div class="stat-bar-fill" style="width:{{ round(($upd/$max)*100) }}%;background:{{ $upd > 5 ? 'var(--warn)' : 'var(--ok)' }}"></div>
      </div>
      <div class="stat-bar-val">{{ $upd }}</div>
    </div>
    @endforeach
  </div>

</div>
</div>
</div>
