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
  <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px">
    <div class="kpi-card k-acc" style="padding:14px 16px">
      <div class="kpi-top"><span class="kpi-label">Kunden</span><span class="ti ti-building-store kpi-icon"></span></div>
      <div class="kpi-value" style="font-size:24px">{{ $customers->count() }}</div>
    </div>
    <div class="kpi-card k-acc" style="padding:14px 16px">
      <div class="kpi-top"><span class="kpi-label">Sites gesamt</span><span class="ti ti-world kpi-icon"></span></div>
      <div class="kpi-value" style="font-size:24px">{{ $totalSites }}</div>
    </div>
    <div class="kpi-card {{ $totalUpdates>0?'k-warn':'k-ok' }}" style="padding:14px 16px">
      <div class="kpi-top"><span class="kpi-label">Updates ausstehend</span><span class="ti ti-refresh-alert kpi-icon"></span></div>
      <div class="kpi-value" style="font-size:24px">{{ $totalUpdates }}</div>
    </div>
    <div class="kpi-card {{ $totalSslCrit>0?'k-crit':'k-ok' }}" style="padding:14px 16px">
      <div class="kpi-top"><span class="kpi-label">SSL kritisch</span><span class="ti ti-certificate-off kpi-icon"></span></div>
      <div class="kpi-value" style="font-size:24px;{{ $totalSslCrit>0?'color:var(--crit)':'' }}">{{ $totalSslCrit }}</div>
    </div>
  </div>

  {{-- Customer Report Cards --}}
  <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:14px">
    @foreach($customers as $c)
    @php
      $sites = $c->sites;
      $totalUpd = $sites->sum('pending_updates');
      $offlineC = $sites->where('status','offline')->count();
      $sslCritC = $sites->filter(fn($s)=>$s->sslDaysLeft()!==null&&$s->sslDaysLeft()<14)->count();
      $openT    = $sites->flatMap->tasks->whereIn('status',['open','in_progress','blocked'])->count();
      $worstSev = $offlineC > 0 || $sslCritC > 0 ? 'crit' : ($totalUpd > 0 || $openT > 0 ? 'warn' : 'ok');
      $colors = ['#0EA5E9','#10B981','#A855F7','#F59E0B','#EF4444','#14B8A6'];
      $col = $colors[$c->id % count($colors)];
    @endphp
    <div class="report-card">
      <div class="report-head">
        <div class="cust-av" style="background:{{ $col }};width:36px;height:36px;font-size:13px">{{ strtoupper(substr($c->name,0,2)) }}</div>
        <div style="flex:1;min-width:0">
          <div style="font-weight:700;font-size:14px">{{ $c->name }}</div>
          <div style="font-size:12px;color:var(--dim)">{{ $sites->count() }} {{ Str::plural('Website', $sites->count()) }}</div>
        </div>
        <span class="badge badge-{{ $worstSev }}">
          {{ match($worstSev) { 'crit'=>'Kritisch','warn'=>'Aufmerksamkeit',default=>'OK' } }}
        </span>
      </div>
      <div class="report-body">
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
        @if($totalUpd > 0)
        <div>
          <div style="font-size:11px;color:var(--faint);margin-bottom:4px">Update-Rückstand</div>
          <div class="stat-bar">
            <div class="stat-bar-fill" style="width:{{ min(100, $totalUpd * 10) }}%;background:{{ $totalUpd>=10?'var(--warn)':'var(--acc)' }}"></div>
          </div>
        </div>
        @endif
      </div>
    </div>
    @endforeach
  </div>

</div>
</div>
</div>
