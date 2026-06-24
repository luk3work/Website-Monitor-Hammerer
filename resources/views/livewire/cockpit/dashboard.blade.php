<div>
{{-- Topbar --}}
<div class="topbar">
  <div class="topbar-title">
    <div class="crumb">Überblick</div>
    <h1>Dashboard</h1>
  </div>
  <div class="tb-search">
    <span class="ti ti-search" style="font-size:15px"></span>
    <input type="text" placeholder="Kunden, Seiten, Domains …">
    <span class="tb-kbd">⌘K</span>
  </div>
  <button class="iconbtn"><span class="ti ti-refresh"></span></button>
  <button class="iconbtn"><span class="ti ti-bell"></span>
    @if($openTasks > 0)<span class="dot"></span>@endif
  </button>
</div>

<div class="scroll">
<div class="pad">

  {{-- KPIs --}}
  <div class="kpis" style="grid-template-columns:repeat(4,1fr);margin-bottom:18px">
    <a href="{{ route('cockpit.kunden') }}" class="card kpi">
      <div class="lab"><span class="ti ti-users"></span>Kunden</div>
      <div class="val">{{ $totalCustomers }}</div>
      <span class="badge b-neutral">aktiv betreut</span>
    </a>
    <a href="{{ route('cockpit.seiten') }}" class="card kpi">
      <div class="lab"><span class="ti ti-world-www"></span>Websites</div>
      <div class="val">{{ $totalSites }}</div>
      <span class="badge b-neutral">überwacht</span>
    </a>
    <a href="{{ route('cockpit.seiten') }}" class="card kpi {{ $critSites > 0 ? 'edge-crit' : '' }}">
      <div class="lab"><span class="ti ti-alert-triangle"></span>Kritische Sites</div>
      <div class="val">{{ $critSites }}</div>
      <span class="badge {{ $critSites > 0 ? 'b-crit' : 'b-ok' }}">{{ $critSites > 0 ? 'sofort prüfen' : 'alle online' }}</span>
    </a>
    <a href="{{ route('cockpit.seiten') }}" class="card kpi {{ $pendingUpdates > 10 ? 'edge-warn' : '' }}">
      <div class="lab"><span class="ti ti-refresh"></span>Offene Updates</div>
      <div class="val">{{ $pendingUpdates }}</div>
      <span class="badge {{ $pendingUpdates > 0 ? 'b-warn' : 'b-ok' }}">Plugins &amp; Themes</span>
    </a>
    <a href="{{ route('cockpit.domains') }}" class="card kpi {{ $sslCrit > 0 ? 'edge-crit' : ($sslSoon > 0 ? 'edge-warn' : '') }}">
      <div class="lab"><span class="ti ti-lock"></span>SSL ≤ 30 Tage</div>
      <div class="val">{{ $sslSoon + $sslCrit }}</div>
      <span class="badge {{ $sslCrit > 0 ? 'b-crit' : ($sslSoon > 0 ? 'b-warn' : 'b-ok') }}">bald fällig</span>
    </a>
    <a href="{{ route('cockpit.seiten') }}" class="card kpi {{ $warnSites > 0 ? 'edge-warn' : '' }}">
      <div class="lab"><span class="ti ti-tool"></span>Wartungsmodus</div>
      <div class="val">{{ $warnSites }}</div>
      <span class="badge {{ $warnSites > 0 ? 'b-warn' : 'b-ok' }}">Seiten</span>
    </a>
    <a href="{{ route('cockpit.seiten') }}" class="card kpi">
      <div class="lab"><span class="ti ti-check"></span>Aufgaben offen</div>
      <div class="val">{{ $openTasks }}</div>
      <span class="badge {{ $openTasks > 0 ? 'b-warn' : 'b-ok' }}">{{ $openTasks > 0 ? 'zu erledigen' : 'alles erledigt' }}</span>
    </a>
    <a href="{{ route('cockpit.domains') }}" class="card kpi">
      <div class="lab"><span class="ti ti-world"></span>Domain-Alerts</div>
      <div class="val">0</div>
      <span class="badge b-ok">alle aktiv</span>
    </a>
  </div>

  {{-- Unteres Grid --}}
  <div class="grid-12">

    {{-- Braucht Aufmerksamkeit --}}
    <div class="col-8 card">
      <div class="sec-h">
        <span class="ti ti-flame"></span>
        <h3>Braucht Aufmerksamkeit</h3>
        <span class="cnt">{{ $issues->count() }}</span>
        <a href="{{ route('cockpit.seiten') }}" class="more">Alle Sites →</a>
      </div>
      @forelse ($issues as $issue)
      <a href="{{ $issue['href'] }}" class="prow">
        <div class="sev {{ $issue['sev'] }}"><span class="ti {{ $issue['ic'] }}"></span></div>
        <div>
          <div class="t">{{ $issue['t'] }}</div>
          <div class="s">{{ $issue['s'] }}</div>
        </div>
        <span class="badge {{ $issue['sev'] === 'crit' ? 'b-crit' : ($issue['sev'] === 'warn' ? 'b-warn' : 'b-info') }}">
          {{ $issue['sev'] === 'crit' ? 'Kritisch' : ($issue['sev'] === 'warn' ? 'Warnung' : 'Info') }}
        </span>
        <div class="meta"><b>jetzt</b>erkannt</div>
      </a>
      @empty
      <div style="padding:48px 18px;text-align:center;color:var(--dim)">
        <div style="font-size:28px;color:var(--ok);margin-bottom:12px">✓</div>
        <div style="font-weight:600">Alles in Ordnung</div>
        <div class="faint" style="font-size:12px;margin-top:4px">Keine offenen Probleme — ruhige Lage.</div>
      </div>
      @endforelse
    </div>

    {{-- Anstehende Abläufe --}}
    <div class="col-4 card">
      <div class="sec-h">
        <span class="ti ti-calendar-due"></span>
        <h3>Anstehende Abläufe</h3>
      </div>
      @forelse ($expiries as $e)
      <a href="{{ $e['href'] }}" class="prow" style="grid-template-columns:auto 1fr auto">
        <div class="sev {{ $e['tone'] }}" style="font-size:9px;font-weight:800;letter-spacing:.04em">{{ $e['tag'] }}</div>
        <div>
          <div class="t" style="font-size:12.5px">{{ $e['name'] }}</div>
          <div class="s">{{ $e['tag'] === 'SSL' ? 'Zertifikat' : ($e['tag'] === 'DOM' ? 'Domain' : 'Lizenz') }}</div>
        </div>
        <div class="meta">
          <b style="color:{{ $e['tone'] === 'crit' ? 'var(--crit)' : ($e['tone'] === 'warn' ? 'var(--warn)' : 'var(--ok)') }}">
            {{ $e['days'] }}d
          </b>
          verbleibend
        </div>
      </a>
      @empty
      <div style="padding:30px 18px;text-align:center;color:var(--dim)">
        <div style="font-size:22px;color:var(--ok);margin-bottom:8px">✓</div>
        <div style="font-size:13px">Keine baldigen Abläufe</div>
      </div>
      @endforelse
    </div>

    {{-- Letzte Aktivität (statisch / Demo) --}}
    <div class="col-12 card">
      <div class="sec-h">
        <span class="ti ti-activity"></span>
        <h3>Systemaktivität</h3>
        <span class="more">Verlauf →</span>
      </div>
      <div class="feed">
        <div class="ic b-ok"><span class="ti ti-circle-check"></span></div>
        <div><div class="tx">Reporter-Heartbeat von <b>{{ $totalSites }} Sites</b> empfangen</div><div class="tm">Heute</div></div>
      </div>
      <div class="feed">
        <div class="ic b-info"><span class="ti ti-refresh"></span></div>
        <div><div class="tx"><b>{{ $pendingUpdates }} Plugin-Updates</b> stehen offen</div><div class="tm">Stand jetzt</div></div>
      </div>
      @if($sslSoon + $sslCrit > 0)
      <div class="feed">
        <div class="ic b-warn"><span class="ti ti-lock-exclamation"></span></div>
        <div><div class="tx"><b>{{ $sslSoon + $sslCrit }} SSL-Zertifikate</b> laufen in ≤ 30 Tagen ab</div><div class="tm">Prüfen</div></div>
      </div>
      @endif
      @if($critSites > 0)
      <div class="feed">
        <div class="ic b-crit"><span class="ti ti-plug-connected-x"></span></div>
        <div><div class="tx"><b>{{ $critSites }} Website{{ $critSites > 1 ? 's' : '' }}</b> {{ $critSites > 1 ? 'sind' : 'ist' }} offline</div><div class="tm">Sofort prüfen</div></div>
      </div>
      @endif
    </div>

  </div>
</div>
</div>
</div>
