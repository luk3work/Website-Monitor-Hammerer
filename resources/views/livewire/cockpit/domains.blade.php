<div>
<div class="topbar">
  <div class="topbar-left">
    <span class="crumb">Überwachung</span>
    <span class="crumb-sep">/</span>
    <h1>Domains & SSL</h1>
  </div>
  <div class="topbar-actions">
    <div class="topbar-search">
      <span class="ti ti-search"></span>
      <input type="text" wire:model.live.debounce.300ms="search" placeholder="Domain oder Kunde…">
    </div>
  </div>
</div>

<div class="scroll">
<div class="pad" style="display:flex;flex-direction:column;gap:16px">

  {{-- Summary --}}
  <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:12px">
    <div class="kpi-card {{ $sslCrit>0?'k-crit':'k-ok' }}" style="padding:14px 16px">
      <div class="kpi-top"><span class="kpi-label">SSL kritisch</span><span class="kpi-ic acc"><span class="ti ti-certificate-off"></span></span></div>
      <div class="kpi-value" style="font-size:24px;{{ $sslCrit>0?'color:var(--crit)':'' }}">{{ $sslCrit }}</div>
      <div class="kpi-sub {{ $sslCrit>0?'crit':'' }}">{{ $sslCrit>0?'< 14 Tage':'Alles ok' }}</div>
    </div>
    <div class="kpi-card {{ $sslWarn>0?'k-warn':'k-ok' }}" style="padding:14px 16px">
      <div class="kpi-top"><span class="kpi-label">SSL bald</span><span class="kpi-ic acc"><span class="ti ti-certificate"></span></span></div>
      <div class="kpi-value" style="font-size:24px;{{ $sslWarn>0?'color:var(--warn)':'' }}">{{ $sslWarn }}</div>
      <div class="kpi-sub {{ $sslWarn>0?'warn':'' }}">14–30 Tage</div>
    </div>
    <div class="kpi-card {{ $domCrit>0?'k-crit':'k-ok' }}" style="padding:14px 16px">
      <div class="kpi-top"><span class="kpi-label">Domain kritisch</span><span class="kpi-ic acc"><span class="ti ti-world-off"></span></span></div>
      <div class="kpi-value" style="font-size:24px;{{ $domCrit>0?'color:var(--crit)':'' }}">{{ $domCrit }}</div>
      <div class="kpi-sub {{ $domCrit>0?'crit':'' }}">{{ $domCrit>0?'< 30 Tage':'Alles ok' }}</div>
    </div>
    <div class="kpi-card {{ $domWarn>0?'k-warn':'k-ok' }}" style="padding:14px 16px">
      <div class="kpi-top"><span class="kpi-label">Domain bald</span><span class="kpi-ic acc"><span class="ti ti-world"></span></span></div>
      <div class="kpi-value" style="font-size:24px;{{ $domWarn>0?'color:var(--warn)':'' }}">{{ $domWarn }}</div>
      <div class="kpi-sub {{ $domWarn>0?'warn':'' }}">30–60 Tage</div>
    </div>
  </div>

  {{-- Filter Chips --}}
  <div class="chip-row">
    <span class="chip {{ !$filterSsl&&!$filterDom ? 'active' : '' }}" wire:click="$set('filterSsl','');$set('filterDom','')">Alle</span>
    <span class="chip {{ $filterSsl==='crit'?'active-crit':'' }}" wire:click="$set('filterSsl', $filterSsl==='crit'?'':'crit')">
      <span class="dot d-crit"></span>SSL kritisch
    </span>
    <span class="chip {{ $filterSsl==='warn'?'active-warn':'' }}" wire:click="$set('filterSsl', $filterSsl==='warn'?'':'warn')">
      <span class="dot d-warn"></span>SSL bald
    </span>
    <span class="chip {{ $filterDom==='crit'?'active-crit':'' }}" wire:click="$set('filterDom', $filterDom==='crit'?'':'crit')">
      <span class="dot d-crit"></span>Domain kritisch
    </span>
    <span class="chip {{ $filterDom==='warn'?'active-warn':'' }}" wire:click="$set('filterDom', $filterDom==='warn'?'':'warn')">
      <span class="dot d-warn"></span>Domain bald
    </span>
  </div>

  {{-- Table --}}
  <div class="card">
    @if($sites->count())
    <table class="tbl">
      <thead>
        <tr>
          <th>Website</th>
          <th>Kunde</th>
          <th>Domain</th>
          <th>Domain-Ablauf</th>
          <th>SSL-Ablauf</th>
          <th>HTTPS</th>
          <th>Hosting</th>
        </tr>
      </thead>
      <tbody>
        @foreach($sites as $site)
        @php
          $ssl = $site->sslDaysLeft();
          $sslCls = $ssl !== null && $ssl < 14 ? 'crit' : ($ssl !== null && $ssl < 30 ? 'warn' : ($ssl !== null ? 'ok' : 'off'));
          $dom = $site->domainDaysLeft();
          $domCls = $dom !== null && $dom < 30 ? 'crit' : ($dom !== null && $dom < 60 ? 'warn' : ($dom !== null ? 'ok' : 'off'));
        @endphp
        <tr>
          <td><a class="lnk" href="{{ route('cockpit.kunden', ['customer' => $site->customer_id, 'site' => $site->id, 'tab' => 'domain']) }}" wire:navigate style="font-weight:600">{{ $site->name }}</a></td>
          <td><a href="{{ route('cockpit.kunden', ['customer' => $site->customer_id]) }}" wire:navigate style="color:var(--dim)">{{ $site->customer?->name ?? '–' }}</a></td>
          <td style="font-size:12.5px;color:var(--dim)">
            @if($site->domain)
              <a href="{{ $site->url }}" target="_blank" rel="noopener" style="color:var(--dim)">{{ $site->domain }} <span class="ti ti-external-link" style="font-size:11px"></span></a>
            @else – @endif
          </td>
          <td>
            @if($dom !== null)
              <span class="days-{{ $domCls }}">{{ $dom }}d</span>
              <span style="font-size:11.5px;color:var(--faint);margin-left:6px">{{ $site->domain_expires_at->format('d.m.Y') }}</span>
            @else
              <span class="days-off">–</span>
            @endif
          </td>
          <td>
            @if($ssl !== null)
              <span class="days-{{ $sslCls }}">{{ $ssl }}d</span>
              <span style="font-size:11.5px;color:var(--faint);margin-left:6px">{{ $site->ssl_expires_at->format('d.m.Y') }}</span>
            @else
              <span class="days-off">–</span>
            @endif
          </td>
          <td>
            @if($site->latestSnapshot?->https)
              <span class="badge badge-ok"><span class="ti ti-lock"></span>HTTPS</span>
            @else
              <span class="badge badge-warn">HTTP</span>
            @endif
          </td>
          <td style="font-size:12px;color:var(--dim)">{{ $site->hosted_by_us ? 'Bei uns' : 'Extern' }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
    @else
    <div class="empty">
      <span class="ti ti-certificate-off"></span>
      <h3>Keine Einträge</h3>
      <p>Keine Sites mit diesen Filterkriterien.</p>
    </div>
    @endif
  </div>

</div>
</div>
</div>
