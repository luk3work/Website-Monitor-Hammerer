<div>
<div class="topbar">
  <div class="topbar-left">
    <span class="crumb">Berichte</span>
    <span class="crumb-sep">/</span>
    <h1>Kunden-Berichte</h1>
  </div>
</div>

<div class="scroll">
<div class="pad" style="display:flex;flex-direction:column;gap:20px">

  {{-- KPI Row --}}
  <div class="kpi-grid">
    <div class="kpi-card k-acc">
      <div class="kpi-top"><span class="kpi-label">Kunden</span><span class="kpi-ic acc"><span class="ti ti-building-store"></span></span></div>
      <div class="kpi-value">{{ $customers->count() }}</div>
      <div class="kpi-sub">aktive Kunden</div>
    </div>
    <div class="kpi-card k-acc">
      <div class="kpi-top"><span class="kpi-label">Sites gesamt</span><span class="kpi-ic acc"><span class="ti ti-world"></span></span></div>
      <div class="kpi-value">{{ $totalSites }}</div>
      <div class="kpi-sub">überwachte Websites</div>
    </div>
    <div class="kpi-card {{ $totalUpdates>0?'k-warn':'k-ok' }}">
      <div class="kpi-top"><span class="kpi-label">Updates ausstehend</span><span class="kpi-ic {{ $totalUpdates>0?'warn':'ok' }}"><span class="ti ti-refresh-alert"></span></span></div>
      <div class="kpi-value" style="{{ $totalUpdates>0?'color:var(--warn)':'' }}">{{ $totalUpdates }}</div>
      <div class="kpi-sub {{ $totalUpdates>0?'warn':'ok' }}">{{ $totalUpdates>0?'Wartung einplanen':'alles aktuell' }}</div>
    </div>
    <div class="kpi-card {{ $totalSslCrit>0?'k-crit':'k-ok' }}">
      <div class="kpi-top"><span class="kpi-label">SSL kritisch</span><span class="kpi-ic {{ $totalSslCrit>0?'crit':'ok' }}"><span class="ti ti-certificate-off"></span></span></div>
      <div class="kpi-value" style="{{ $totalSslCrit>0?'color:var(--crit)':'' }}">{{ $totalSslCrit }}</div>
      <div class="kpi-sub {{ $totalSslCrit>0?'crit':'ok' }}">{{ $totalSslCrit>0?'sofort handeln':'alle SSL ok' }}</div>
    </div>
  </div>

  {{-- Customer Report Cards --}}
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(360px,1fr));gap:16px">
    @forelse($customers as $c)
    @php
      $sites = $c->sites;
      $totalUpd = $sites->sum('pending_updates');
      $offlineC = $sites->filter(fn($s)=>$s->status?->value==='offline')->count();
      $sslCritC = $sites->filter(fn($s)=>$s->sslDaysLeft()!==null&&$s->sslDaysLeft()<14)->count();
      $openT    = $sites->flatMap->tasks->whereIn('status',['open','in_progress','blocked'])->count();
      $worstSev = $offlineC > 0 || $sslCritC > 0 ? 'crit' : ($totalUpd > 0 || $openT > 0 ? 'warn' : 'ok');
      $colors = ['#B9A564','#10B981','#A855F7','#F59E0B','#EF4444','#14B8A6'];
      $col = $colors[$c->id % count($colors)];
    @endphp
    <a class="report-card" href="{{ route('cockpit.kunden', ['customer' => $c->id]) }}" wire:navigate>
      <div class="report-head">
        <div class="cust-av" style="background:{{ $col }};width:38px;height:38px;font-size:14px">{{ strtoupper(substr($c->name,0,2)) }}</div>
        <div style="flex:1;min-width:0">
          <div style="font-weight:700;font-size:14.5px">{{ $c->name }}</div>
          <div style="font-size:12px;color:var(--dim)">{{ $sites->count() }} {{ Str::plural('Website', $sites->count()) }}</div>
        </div>
        <span class="badge badge-{{ $worstSev }}">
          {{ match($worstSev) { 'crit'=>'Kritisch','warn'=>'Aufmerksamkeit',default=>'OK' } }}
        </span>
      </div>
      <div class="report-body">
        <div class="report-stats">
          <div class="report-stat">
            <span class="report-stat-label"><span class="ti ti-wifi-off" style="color:var(--{{ $offlineC>0?'crit':'faint' }})"></span> Offline</span>
            <span class="report-stat-val" style="{{ $offlineC>0?'color:var(--crit)':'' }}">{{ $offlineC }}</span>
          </div>
          <div class="report-stat">
            <span class="report-stat-label"><span class="ti ti-refresh-alert" style="color:var(--{{ $totalUpd>0?'warn':'faint' }})"></span> Updates</span>
            <span class="report-stat-val" style="{{ $totalUpd>0?'color:var(--warn)':'' }}">{{ $totalUpd }}</span>
          </div>
          <div class="report-stat">
            <span class="report-stat-label"><span class="ti ti-certificate-off" style="color:var(--{{ $sslCritC>0?'crit':'faint' }})"></span> SSL kritisch</span>
            <span class="report-stat-val" style="{{ $sslCritC>0?'color:var(--crit)':'' }}">{{ $sslCritC }}</span>
          </div>
          <div class="report-stat">
            <span class="report-stat-label"><span class="ti ti-checklist" style="color:var(--{{ $openT>0?'acc':'faint' }})"></span> Offene Tasks</span>
            <span class="report-stat-val">{{ $openT }}</span>
          </div>
        </div>
        @if($totalUpd > 0)
        <div>
          <div style="font-size:11px;color:var(--faint);margin-bottom:5px">Update-Rückstand</div>
          <div class="stat-bar">
            <div class="stat-bar-fill" style="width:{{ min(100, $totalUpd * 10) }}%;{{ $totalUpd>=10?'background:var(--warn)':'' }}"></div>
          </div>
        </div>
        @endif
      </div>
    </a>
    @empty
    <div class="empty"><span class="ti ti-report"></span><h3>Keine Kunden</h3><p>Sobald Kunden mit Websites angelegt sind, erscheinen hier ihre Berichte.</p></div>
    @endforelse
  </div>

</div>
</div>
</div>
