<div style="display:flex;flex-direction:column;height:100vh;overflow:hidden">

{{-- Topbar --}}
<div class="topbar">
  <div class="topbar-left">
    <span class="crumb">Überwachung</span>
    <span class="crumb-sep">/</span>
    <h1>Kunden</h1>
  </div>
  <div class="topbar-actions">
    <div class="topbar-search">
      <span class="ti ti-search"></span>
      <input type="text" wire:model.live.debounce.300ms="search" placeholder="Kunden suchen…" aria-label="Kunden suchen">
    </div>
  </div>
</div>

{{-- Master-Detail --}}
<div class="md-layout flex-1">

  {{-- Customer List --}}
  <div class="md-list">
    @forelse($customers as $c)
    @php
      $sev = $c->_severity;
      $dotCls = match($sev) { 'critical'=>'d-crit','warning'=>'d-warn',default=>'d-ok' };
      $colors = ['#0EA5E9','#10B981','#A855F7','#F59E0B','#EF4444','#14B8A6','#F97316'];
      $col = $colors[$c->id % count($colors)];
    @endphp
    <div class="cust-item {{ $customerId === $c->id ? 'active' : '' }}"
         wire:click="selectCustomer({{ $c->id }})" role="button" tabindex="0">
      <div class="cust-av" style="background:{{ $col }}">{{ strtoupper(substr($c->name,0,2)) }}</div>
      <div style="flex:1;min-width:0">
        <div class="cust-name truncate">{{ $c->name }}</div>
        <div class="cust-sub">{{ $c->sites->count() }} {{ Str::plural('Website', $c->sites->count()) }}</div>
      </div>
      <span class="dot {{ $dotCls }}"></span>
    </div>
    @empty
    <div class="empty"><span class="ti ti-building-store"></span><h3>Keine Kunden</h3></div>
    @endforelse
  </div>

  {{-- Detail --}}
  <div class="md-detail">
    @if($customer)

      {{-- Customer Header --}}
      @php
        $colors = ['#0EA5E9','#10B981','#A855F7','#F59E0B','#EF4444','#14B8A6','#F97316'];
        $col = $colors[$customer->id % count($colors)];
        $allSites = $customer->sites;
        $openTasksCount = $allSites->flatMap->tasks->where('status', '!=', 'done')->where('status', '!=', 'dismissed')->count();
      @endphp
      <div class="detail-header">
        <div class="detail-av" style="background:{{ $col }}">{{ strtoupper(substr($customer->name,0,2)) }}</div>
        <div style="flex:1;min-width:0">
          <div style="font-size:18px;font-weight:800;letter-spacing:-0.02em">{{ $customer->name }}</div>
          <div style="font-size:12.5px;color:var(--dim);margin-top:3px;display:flex;gap:12px;flex-wrap:wrap">
            <span><span class="ti ti-world" style="font-size:13px"></span> {{ $allSites->count() }} Sites</span>
            @if($openTasksCount > 0)
            <span style="color:var(--warn)"><span class="ti ti-alert-circle" style="font-size:13px"></span> {{ $openTasksCount }} offene Aufgaben</span>
            @endif
          </div>
        </div>
        <a href="{{ route('cockpit.tasks') }}" class="btn ghost sm"><span class="ti ti-checklist"></span>Aufgaben</a>
      </div>

      {{-- Site Selector (if > 1 site) --}}
      @if($allSites->count() > 1)
      <div style="padding:10px 20px;border-bottom:1px solid var(--line);display:flex;gap:6px;flex-wrap:wrap">
        <span class="chip {{ !$siteId ? 'active' : '' }}" wire:click="$set('siteId',null)">Alle Sites</span>
        @foreach($allSites as $s)
        @php $ssDot = match($s->status?->value) { 'online'=>'ok','offline'=>'crit','maintenance'=>'warn',default=>'off' }; @endphp
        <span class="chip {{ $siteId === $s->id ? 'active' : '' }}" wire:click="selectSite({{ $s->id }})">
          <span class="dot d-{{ $ssDot }}"></span>{{ $s->name }}
        </span>
        @endforeach
      </div>
      @endif

      {{-- Tabs --}}
      <div class="tabs">
        <div class="tab {{ $tab==='overview' ? 'active' : '' }}" wire:click="setTab('overview')">Übersicht</div>
        @if($currentSite || $allSites->count() === 1)
        <div class="tab {{ $tab==='plugins' ? 'active' : '' }}" wire:click="setTab('plugins')">Plugins</div>
        <div class="tab {{ $tab==='ssl' ? 'active' : '' }}" wire:click="setTab('ssl')">Domain & SSL</div>
        <div class="tab {{ $tab==='packages' ? 'active' : '' }}" wire:click="setTab('packages')">Pakete</div>
        @endif
        <div class="tab {{ $tab==='tasks' ? 'active' : '' }}" wire:click="setTab('tasks')">Aufgaben</div>
      </div>

      {{-- Tab Content --}}
      <div class="scroll" style="flex:1">
      <div class="pad">

        @if($tab === 'overview')
        @php $site = $currentSite ?? $allSites->first(); @endphp
        @if($site)
        {{-- Health Cards --}}
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:16px">
          @php $ssl = $site->sslDaysLeft(); $sslCls = $ssl !== null && $ssl < 14 ? 'crit' : ($ssl !== null && $ssl < 30 ? 'warn' : 'ok'); @endphp
          <div class="kpi-card k-{{ $sslCls }}" style="padding:14px 16px">
            <div class="kpi-top"><span class="kpi-label">SSL</span><span class="kpi-ic acc"><span class="ti ti-certificate"></span></span></div>
            <div class="kpi-value" style="font-size:22px;color:var(--{{ $sslCls }})">{{ $ssl !== null ? $ssl.'d' : '–' }}</div>
            <div class="kpi-sub {{ $sslCls }}">{{ $ssl !== null ? ($ssl<=0?'Abgelaufen':$site->ssl_expires_at->format('d.m.Y')) : 'Unbekannt' }}</div>
          </div>
          @php $dom = $site->domainDaysLeft(); $domCls = $dom !== null && $dom < 30 ? 'crit' : ($dom !== null && $dom < 60 ? 'warn' : 'ok'); @endphp
          <div class="kpi-card k-{{ $domCls }}" style="padding:14px 16px">
            <div class="kpi-top"><span class="kpi-label">Domain</span><span class="kpi-ic acc"><span class="ti ti-world"></span></span></div>
            <div class="kpi-value" style="font-size:22px;color:var(--{{ $domCls }})">{{ $dom !== null ? $dom.'d' : '–' }}</div>
            <div class="kpi-sub {{ $domCls }}">{{ $dom !== null ? $site->domain_expires_at->format('d.m.Y') : 'Unbekannt' }}</div>
          </div>
          <div class="kpi-card k-acc" style="padding:14px 16px">
            <div class="kpi-top"><span class="kpi-label">Updates</span><span class="kpi-ic acc"><span class="ti ti-refresh-alert"></span></span></div>
            <div class="kpi-value" style="font-size:22px">{{ $site->pending_updates ?? 0 }}</div>
            <div class="kpi-sub">Ausstehend</div>
          </div>
          <div class="kpi-card k-{{ $site->status?->value === 'online' ? 'ok' : 'crit' }}" style="padding:14px 16px">
            <div class="kpi-top"><span class="kpi-label">Status</span><span class="kpi-ic acc"><span class="ti ti-pulse"></span></span></div>
            <div class="kpi-value" style="font-size:15px;font-weight:700">{{ $site->status?->label() ?? '–' }}</div>
            <div class="kpi-sub {{ $site->status?->value === 'online' ? 'ok' : 'crit' }}">
              {{ $site->last_seen_at ? 'Zuletzt: '.$site->last_seen_at->diffForHumans() : 'Nie gesehen' }}
            </div>
          </div>
        </div>

        {{-- KV Details --}}
        <div class="card">
          <div class="sec-h"><span class="ti ti-info-circle"></span><h3>Site-Details</h3></div>
          <div class="kv-grid">
            <div class="kv"><div class="kv-label">URL</div><div class="kv-val">{{ $site->url ?? $site->domain ?? '–' }}</div></div>
            <div class="kv"><div class="kv-label">CMS</div><div class="kv-val">{{ $site->cms_type ?? '–' }}</div></div>
            <div class="kv"><div class="kv-label">Hosting</div><div class="kv-val">{{ $site->hosted_by_us ? 'Bei uns' : 'Extern' }}</div></div>
            <div class="kv"><div class="kv-label">Domain</div><div class="kv-val">{{ $site->domain_by_us ? 'Bei uns' : 'Extern' }}</div></div>
            <div class="kv"><div class="kv-label">PHP</div><div class="kv-val">{{ $site->php_version ?? '–' }}</div></div>
            <div class="kv"><div class="kv-label">WP Version</div><div class="kv-val">{{ $site->wp_version ?? '–' }}</div></div>
          </div>
        </div>
        @endif

        @elseif($tab === 'plugins')
        @php $site = $currentSite ?? $allSites->first(); @endphp
        @if($site)
        <div class="card">
          <div class="sec-h"><span class="ti ti-puzzle"></span><h3>Plugins</h3></div>
          @php $plugins = $site->plugins()->orderByDesc('update_available')->orderBy('name')->get(); @endphp
          @if($plugins->count())
          <table class="tbl">
            <thead><tr><th>Plugin</th><th>Version</th><th>Status</th></tr></thead>
            <tbody>
              @foreach($plugins as $p)
              <tr>
                <td style="font-weight:600">{{ $p->name }}</td>
                <td style="color:var(--dim)">{{ $p->version ?? '–' }}</td>
                <td>
                  @if($p->update_available)
                    <span class="badge badge-warn"><span class="ti ti-refresh"></span>Update verfügbar</span>
                  @else
                    <span class="badge badge-ok"><span class="ti ti-check"></span>Aktuell</span>
                  @endif
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
          @else
          <div class="empty"><span class="ti ti-puzzle-off"></span><p>Keine Plugin-Daten vorhanden.</p></div>
          @endif
        </div>
        @endif

        @elseif($tab === 'ssl')
        @php $site = $currentSite ?? $allSites->first(); @endphp
        @if($site)
        <div class="card">
          <div class="sec-h"><span class="ti ti-certificate"></span><h3>Domain & SSL</h3></div>
          <div class="kv-grid">
            <div class="kv"><div class="kv-label">Domain</div><div class="kv-val">{{ $site->domain ?? '–' }}</div></div>
            <div class="kv"><div class="kv-label">Domain-Ablauf</div><div class="kv-val">{{ $site->domain_expires_at?->format('d.m.Y') ?? '–' }}</div></div>
            <div class="kv"><div class="kv-label">Domain von uns</div><div class="kv-val">{{ $site->domain_by_us ? 'Ja' : 'Nein' }}</div></div>
            <div class="kv"><div class="kv-label">SSL-Ablauf</div><div class="kv-val">{{ $site->ssl_expires_at?->format('d.m.Y') ?? '–' }}</div></div>
            <div class="kv"><div class="kv-label">HTTPS</div><div class="kv-val">{{ ($site->latestSnapshot?->https) ? 'Ja' : 'Nein' }}</div></div>
            <div class="kv"><div class="kv-label">Hosting von uns</div><div class="kv-val">{{ $site->hosted_by_us ? 'Ja' : 'Nein' }}</div></div>
          </div>
        </div>
        @endif

        @elseif($tab === 'packages')
        @php $site = $currentSite ?? $allSites->first(); @endphp
        @if($site)
        <div class="card">
          <div class="sec-h"><span class="ti ti-package"></span><h3>Gebuchte Pakete</h3><span class="cnt">{{ $site->packages->where('pivot.state','booked')->count() }}</span></div>
          @forelse($site->packages->where('pivot.state','booked') as $pkg)
          <div class="prow">
            <span class="ti ti-check text-acc prow-icon"></span>
            <div class="prow-body">
              <div class="prow-title">{{ $pkg->name }}</div>
              <div class="prow-meta"><span>{{ $pkg->group }}</span><span class="sep">·</span><span>{{ $pkg->priceLabel() }}</span></div>
            </div>
          </div>
          @empty
          <div class="empty"><span class="ti ti-package-off"></span><p>Keine Pakete gebucht.</p></div>
          @endforelse
        </div>
        @endif

        @elseif($tab === 'tasks')
        @php
          $siteTasks = $allSites->flatMap->tasks->sortBy(fn($t) => match($t->severity?->value ?? $t->severity) { 'critical'=>0,'warning'=>1,default=>2 });
        @endphp
        <div class="card">
          <div class="sec-h"><span class="ti ti-checklist"></span><h3>Aufgaben</h3><span class="cnt">{{ $siteTasks->count() }}</span>
            <a href="{{ route('cockpit.tasks') }}" class="btn ghost sm ml-auto"><span class="ti ti-external-link"></span>Alle Tasks</a>
          </div>
          @forelse($siteTasks as $t)
          @php $sc = match($t->severity?->value??$t->severity) { 'critical'=>'crit','warning'=>'warn',default=>'info' }; @endphp
          <div class="prow">
            <span class="dot d-{{ $sc }}" style="margin-top:5px"></span>
            <div class="prow-body">
              <div class="prow-title">{{ $t->title }}</div>
              <div class="prow-meta">
                <span>{{ $t->site?->name }}</span>
                <span class="sep">·</span>
                <span class="badge badge-{{ match($t->status?->value??$t->status){'done'=>'ok','dismissed'=>'off','open'=>'warn',default=>'acc'} }}" style="font-size:10px">{{ $t->status?->label()??$t->status }}</span>
              </div>
            </div>
          </div>
          @empty
          <div class="empty"><span class="ti ti-circle-check" style="color:var(--ok)"></span><h3>Keine Aufgaben</h3><p>Für diesen Kunden gibt es aktuell keine offenen Tasks.</p></div>
          @endforelse
        </div>
        @endif

      </div>
      </div>

    @else
    {{-- No selection --}}
    <div class="empty" style="height:100%">
      <span class="ti ti-building-store" style="font-size:48px"></span>
      <h3>Kunden wählen</h3>
      <p>Wähle links einen Kunden aus, um Details zu sehen.</p>
    </div>
    @endif
  </div>

</div>
</div>
