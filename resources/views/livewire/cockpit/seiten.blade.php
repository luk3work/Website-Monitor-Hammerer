<div>
<div class="topbar">
  <div class="topbar-left">
    <span class="crumb">Überwachung</span>
    <span class="crumb-sep">/</span>
    <h1>Websites</h1>
  </div>
  <div class="topbar-actions">
    <div class="topbar-search">
      <span class="ti ti-search"></span>
      <input type="text" wire:model.live.debounce.300ms="search" placeholder="Site oder Kunde suchen…">
    </div>
  </div>
</div>

<div class="scroll">
<div class="pad" style="display:flex;flex-direction:column;gap:16px">

  {{-- Summary chips --}}
  <div class="chip-row">
    <span class="chip {{ !$filterStatus ? 'active' : '' }}" wire:click="$set('filterStatus','')">
      Alle <strong style="margin-left:3px">{{ $totalCount }}</strong>
    </span>
    <span class="chip {{ $filterStatus==='offline' ? 'active-crit' : '' }}" wire:click="$set('filterStatus', $filterStatus==='offline'?'':'offline')">
      <span class="dot d-crit"></span>Offline <strong style="margin-left:3px">{{ $offlineCount }}</strong>
    </span>
    <span class="chip {{ $filterStatus==='online' ? 'active-ok' : '' }}" wire:click="$set('filterStatus', $filterStatus==='online'?'':'online')">
      <span class="dot d-ok"></span>Online
    </span>
    <span class="chip {{ $filterSsl==='crit' ? 'active-crit' : '' }}" wire:click="$set('filterSsl', $filterSsl==='crit'?'':'crit')">
      <span class="ti ti-certificate-off"></span>SSL kritisch <strong style="margin-left:3px">{{ $sslCritCount }}</strong>
    </span>
    <span class="chip {{ $filterSsl==='warn' ? 'active-warn' : '' }}" wire:click="$set('filterSsl', $filterSsl==='warn'?'':'warn')">
      <span class="ti ti-certificate"></span>SSL bald
    </span>
  </div>

  {{-- Table --}}
  <div class="card">
    @if($sites->count() > 0)
    <table class="tbl">
      <thead>
        <tr>
          <th>Status</th>
          <th>Website</th>
          <th>Kunde</th>
          <th>CMS</th>
          <th>SSL</th>
          <th>Domain</th>
          <th>Updates</th>
          <th>Pakete</th>
          <th>Zuletzt</th>
        </tr>
      </thead>
      <tbody>
        @foreach($sites as $site)
        @php
          $stDot = match($site->status?->value) { 'online'=>'ok','offline'=>'crit','maintenance'=>'maint',default=>'off' };
          $ssl = $site->sslDaysLeft();
          $sslCls = $ssl !== null && $ssl < 14 ? 'crit' : ($ssl !== null && $ssl < 30 ? 'warn' : ($ssl !== null ? 'ok' : 'off'));
          $dom = $site->domainDaysLeft();
          $domCls = $dom !== null && $dom < 30 ? 'crit' : ($dom !== null && $dom < 60 ? 'warn' : ($dom !== null ? 'ok' : 'off'));
          $bookedPkgs = $site->packages->where('pivot.state','booked');
        @endphp
        <tr>
          <td>
            <span class="dot {{ $stDot === 'maint' ? 'd-maint' : 'd-'.$stDot }}" title="{{ $site->status?->label() }}"></span>
          </td>
          <td>
            <div style="font-weight:600">{{ $site->name }}</div>
            @if($site->domain)
            <div style="font-size:11.5px;color:var(--faint)">{{ $site->domain }}</div>
            @endif
          </td>
          <td>
            <a href="{{ route('cockpit.kunden') }}" style="color:var(--dim)">{{ $site->customer?->name ?? '–' }}</a>
          </td>
          <td><span style="color:var(--dim);font-size:12.5px">{{ $site->cms_type ?? '–' }}</span></td>
          <td>
            @if($ssl !== null)
              <span class="days-{{ $sslCls }}">{{ $ssl }}d</span>
            @else
              <span class="days-off">–</span>
            @endif
          </td>
          <td>
            @if($dom !== null)
              <span class="days-{{ $domCls }}">{{ $dom }}d</span>
            @else
              <span class="days-off">–</span>
            @endif
          </td>
          <td>
            @if(($site->pending_updates ?? 0) > 0)
              <span class="badge badge-{{ $site->pending_updates >= 5 ? 'warn' : 'info' }}">{{ $site->pending_updates }}</span>
            @else
              <span style="color:var(--faint)">–</span>
            @endif
          </td>
          <td>
            @foreach($bookedPkgs->take(2) as $pkg)
              <span class="pkg-chip booked" style="font-size:10.5px">{{ Str::limit($pkg->name,18) }}</span>
            @endforeach
            @if($bookedPkgs->count() > 2)
              <span class="pkg-chip" style="font-size:10.5px">+{{ $bookedPkgs->count()-2 }}</span>
            @endif
          </td>
          <td>
            <span style="font-size:12px;color:var(--faint)">
              {{ $site->last_seen_at ? $site->last_seen_at->diffForHumans(null, true) : '–' }}
            </span>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
    <div style="padding:12px 16px;border-top:1px solid var(--line)">{{ $sites->links() }}</div>
    @else
    <div class="empty">
      <span class="ti ti-world-off"></span>
      <h3>Keine Sites gefunden</h3>
      <p>Keine Websites mit diesen Filtern.</p>
    </div>
    @endif
  </div>

</div>
</div>
</div>
