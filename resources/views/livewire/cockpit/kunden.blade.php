<div style="display:contents">
{{-- Topbar --}}
<div class="topbar">
  <div class="topbar-title">
    <div class="crumb">Betrieb</div>
    <h1>Kunden</h1>
  </div>
  <button class="btn acc" wire:click="$dispatch('open-modal', {id:'new-customer'})">
    <span class="ti ti-plus"></span>Neuer Kunde
  </button>
  <button class="iconbtn"><span class="ti ti-refresh"></span></button>
</div>

{{-- Master-Detail --}}
<div class="md" style="flex:1;overflow:hidden">

  {{-- Kundenliste --}}
  <div class="clist">
    <div class="ch">
      <div class="srch">
        <span class="ti ti-search" style="font-size:14px"></span>
        <input type="text" placeholder="Kunde suchen …" wire:model.live.debounce.250ms="search">
      </div>
      <div class="filters" style="margin-top:10px">
        <button class="chip {{ $filter === 'all'      ? 'on' : '' }}" wire:click="setFilter('all')">Alle</button>
        <button class="chip {{ $filter === 'problems' ? 'on' : '' }}" wire:click="setFilter('problems')">Probleme</button>
        <button class="chip {{ $filter === 'critical' ? 'on' : '' }}" wire:click="setFilter('critical')">Kritisch</button>
      </div>
    </div>
    <div class="citems">
      @forelse ($customers as $c)
        @php $sev = $this->customerSeverity($c); $col = $pal[$c->id % count($pal)]; @endphp
        <button class="citem {{ $customerId === $c->id ? 'on' : '' }}" wire:click="selectCustomer({{ $c->id }})">
          <span class="logo" style="background:{{ $col }}">{{ mb_substr($c->name,0,1) }}</span>
          <span style="min-width:0;flex:1;text-align:left">
            <span class="nm">
              <span class="dot {{ $c->sites->first()?->status->value === 'online' ? 'd-ok' : ($c->sites->first()?->status->value === 'offline' ? 'd-crit' : 'd-warn') }}"></span>
              {{ $c->name }}
            </span>
            <span class="sub">{{ $c->sites->count() }} Website{{ $c->sites->count() === 1 ? '' : 's' }}@if($c->company) · {{ $c->company }}@endif</span>
          </span>
          <span class="wbadge">
            @if($sev === 'crit')
              <span class="badge b-crit">Kritisch</span>
            @elseif($sev === 'warn')
              <span class="badge b-warn">Warnung</span>
            @else
              <span class="ti ti-circle-check" style="color:var(--ok);font-size:17px"></span>
            @endif
          </span>
        </button>
      @empty
        <div style="padding:40px 16px;text-align:center;color:var(--dim)">
          <div style="font-size:28px;color:var(--faint);margin-bottom:8px">☖</div>
          <div>Keine Kunden gefunden</div>
        </div>
      @endforelse
    </div>
  </div>

  {{-- Detail-Bereich --}}
  <div class="detail">
    @if(!$customer)
      <div class="stub" style="padding-top:100px">
        <span class="ti ti-users" style="font-size:48px"></span>
        <h3>Kein Kunde ausgewählt</h3>
        <p>Wähle links einen Kunden aus der Liste.</p>
      </div>
    @else
      @php
        $col  = $pal[$customer->id % count($pal)];
        $sev  = $this->customerSeverity($customer);
        $sites = $customer->sites;
      @endphp

      {{-- Detail-Kopf --}}
      <div class="dhead">
        <div class="big" style="background:{{ $col }}">{{ mb_substr($customer->name,0,1) }}</div>
        <div style="min-width:0;flex:1">
          <h2>
            {{ $customer->name }}
            <span class="badge {{ $sev === 'crit' ? 'b-crit' : ($sev === 'warn' ? 'b-warn' : 'b-ok') }}">
              {{ $sev === 'crit' ? 'Kritisch' : ($sev === 'warn' ? 'Warnung' : 'Alles ok') }}
            </span>
          </h2>
          <div class="dsub">
            @if($customer->company)<span><span class="ti ti-building" style="font-size:13px"></span>{{ $customer->company }}</span>@endif
            @if($customer->email)<span>· {{ $customer->email }}</span>@endif
            @if($sites->isNotEmpty())
              <span class="sitesel">
                <span class="ti ti-world-www"></span>
                <select wire:model.live="siteId">
                  @foreach($sites as $s)
                    <option value="{{ $s->id }}">{{ $s->label ?? $s->url }}</option>
                  @endforeach
                </select>
              </span>
            @endif
          </div>
        </div>
        <div class="dactions">
          @if($site)<a href="{{ $site->url }}" target="_blank" rel="noopener" class="btn ghost"><span class="ti ti-external-link"></span>Website</a>@endif
          <button class="btn ghost"><span class="ti ti-key"></span>Backend</button>
          <button class="btn acc"><span class="ti ti-report"></span>Report</button>
        </div>
      </div>

      @if($site)
      {{-- Detail-Body --}}
      <div class="dbody">
        <div style="display:flex;flex-direction:column;gap:18px;min-width:0">

          {{-- Health-Cards --}}
          <div class="health">
            <div class="hcard">
              <div class="hl"><span class="ti ti-activity-heartbeat"></span>Status</div>
              <div class="hv">
                <span class="dot {{ $site->status->value === 'online' ? 'd-ok' : ($site->status->value === 'offline' ? 'd-crit' : 'd-warn') }}"></span>
                {{ match($site->status->value) { 'online' => 'Online', 'offline' => 'Offline', 'maintenance' => 'Wartung', default => 'Unbekannt' } }}
              </div>
              <div class="hs">{{ $site->last_seen_at ? 'Zuletzt: '.$site->last_seen_at->diffForHumans() : 'Noch kein Check' }}</div>
            </div>
            <div class="hcard">
              <div class="hl"><span class="ti ti-lock"></span>SSL-Zertifikat</div>
              <div class="hv">
                @if($site->ssl_expires_at)
                  @php $d = (int)$site->ssl_expires_at->diffInDays(now(), false); @endphp
                  <span class="dot {{ $d <= 7 ? 'd-crit' : ($d <= 30 ? 'd-warn' : 'd-ok') }}"></span>
                  {{ $d <= 0 ? 'Abgelaufen' : 'Gültig' }}
                @else
                  <span class="dot d-off"></span>Unbekannt
                @endif
              </div>
              <div class="hs">{{ $site->ssl_expires_at ? 'Läuft ab: '.$site->ssl_expires_at->format('d.m.Y') : '–' }}</div>
            </div>
            <div class="hcard">
              <div class="hl"><span class="ti ti-refresh"></span>Updates</div>
              <div class="hv">
                <span class="dot {{ $site->pending_updates > 0 ? 'd-warn' : 'd-ok' }}"></span>
                {{ $site->pending_updates }} offen
              </div>
              <div class="hs">WP {{ $site->wp_version ?? '–' }}</div>
            </div>
            <div class="hcard">
              <div class="hl"><span class="ti ti-world"></span>Domain</div>
              <div class="hv">
                @if($site->domain_expires_at)
                  @php $dd = (int)$site->domain_expires_at->diffInDays(now(), false); @endphp
                  <span class="dot {{ $dd <= 30 ? 'd-crit' : ($dd <= 60 ? 'd-warn' : 'd-ok') }}"></span>
                  {{ $dd }}d
                @else
                  <span class="dot d-off"></span>–
                @endif
              </div>
              <div class="hs">{{ parse_url($site->url, PHP_URL_HOST) ?? $site->url }}</div>
            </div>
          </div>

          {{-- Tabs --}}
          <div class="tabs">
            @foreach(['overview' => ['ti-layout-grid','Übersicht'], 'plugins' => ['ti-plug','Plugins'], 'ssl' => ['ti-lock','Domain & SSL'], 'security' => ['ti-shield','Security'], 'licenses' => ['ti-certificate','Lizenzen']] as $key => $t)
              <button class="tab {{ $tab === $key ? 'on' : '' }}" wire:click="setTab('{{ $key }}')">
                <span class="ti {{ $t[0] }}"></span>{{ $t[1] }}
              </button>
            @endforeach
          </div>

          {{-- Tab-Inhalt --}}
          <div class="card">
            @if($tab === 'overview')
              <div style="padding:16px">
                <div class="kv">
                  <div class="cell"><div class="k"><span class="ti ti-link" style="font-size:13px"></span>URL</div><div class="v"><a href="{{ $site->url }}" target="_blank" style="color:var(--info)">{{ $site->url }}</a></div></div>
                  <div class="cell"><div class="k"><span class="ti ti-server" style="font-size:13px"></span>Hosting</div><div class="v">{{ $site->hosting_note ?? 'Nicht angegeben' }}</div></div>
                  <div class="cell"><div class="k"><span class="ti ti-brand-wordpress" style="font-size:13px"></span>CMS</div><div class="v">WordPress {{ $site->wp_version ?? '–' }}</div></div>
                  <div class="cell"><div class="k"><span class="ti ti-plug" style="font-size:13px"></span>Plugins</div><div class="v">{{ $site->plugins->count() }} installiert · {{ $site->pending_updates }} Updates</div></div>
                  <div class="cell"><div class="k"><span class="ti ti-package" style="font-size:13px"></span>Paket</div><div class="v">{{ '–' }}</div></div>
                  <div class="cell"><div class="k"><span class="ti ti-clock-check" style="font-size:13px"></span>Letzter Report</div><div class="v">{{ $site->last_seen_at?->diffForHumans() ?? '–' }}</div></div>
                </div>
              </div>

            @elseif($tab === 'plugins')
              <table class="tbl">
                <thead><tr><th>Plugin</th><th>Installiert</th><th>Status</th><th>Aktiv</th></tr></thead>
                <tbody>
                  @forelse($site->plugins->sortByDesc('update_available') as $p)
                  <tr>
                    <td style="font-weight:600">{{ $p->name }}</td>
                    <td class="muted">{{ $p->version ?? '–' }}</td>
                    <td>
                      @if($p->update_available ?? false)
                        <span class="badge b-warn">Update verfügbar</span>
                      @else
                        <span class="badge b-ok">aktuell</span>
                      @endif
                    </td>
                    <td><span class="dot {{ ($p->is_active ?? true) ? 'd-ok' : 'd-off' }}"></span></td>
                  </tr>
                  @empty
                  <tr><td colspan="4" style="text-align:center;color:var(--faint);padding:32px">Keine Plugin-Daten — warte auf Reporter-Snapshot.</td></tr>
                  @endforelse
                </tbody>
              </table>

            @elseif($tab === 'ssl')
              <div style="padding:16px">
                <div class="kv">
                  <div class="cell"><div class="k"><span class="ti ti-world" style="font-size:13px"></span>Domain</div><div class="v">{{ parse_url($site->url, PHP_URL_HOST) ?? $site->url }}</div></div>
                  <div class="cell"><div class="k">Domain-Ablauf</div><div class="v">{{ $site->domain_expires_at ? $site->domain_expires_at->format('d.m.Y').' ('.(int)$site->domain_expires_at->diffInDays(now(), false).'d)' : '–' }}</div></div>
                  <div class="cell"><div class="k">Domain bei uns</div><div class="v">{{ $site->domain_by_us ? 'Ja' : 'Extern' }}</div></div>
                  <div class="cell"><div class="k"><span class="ti ti-lock" style="font-size:13px"></span>SSL gültig bis</div><div class="v">{{ $site->ssl_expires_at ? $site->ssl_expires_at->format('d.m.Y').' ('.(int)$site->ssl_expires_at->diffInDays(now(), false).'d)' : '–' }}</div></div>
                  <div class="cell"><div class="k">SSL-Aussteller</div><div class="v">Let's Encrypt</div></div>
                  <div class="cell"><div class="k">Auto-Renew</div><div class="v"><span class="dot d-ok"></span>aktiv</div></div>
                </div>
              </div>

            @elseif($tab === 'security')
              <div style="padding:16px;display:flex;flex-direction:column;gap:14px">
                <div style="display:flex;align-items:center;gap:12px">
                  <span class="badge b-ok">Keine bekannten Bedrohungen</span>
                  <span class="faint" style="font-size:12px">Reporter meldet keine Security-Signale</span>
                </div>
                <table class="tbl">
                  <thead><tr><th>Prüfung</th><th>Ergebnis</th></tr></thead>
                  <tbody>
                    <tr><td>Reporter aktiv</td><td><span class="badge {{ $site->last_seen_at && $site->last_seen_at->gt(now()->subHours(25)) ? 'b-ok' : 'b-warn' }}">{{ $site->last_seen_at && $site->last_seen_at->gt(now()->subHours(25)) ? 'aktiv' : 'inaktiv / veraltet' }}</span></td></tr>
                    <tr><td>Letzte Meldung</td><td class="muted">{{ $site->last_seen_at?->diffForHumans() ?? '–' }}</td></tr>
                    <tr><td>Pending Updates</td><td><span class="badge {{ $site->pending_updates > 0 ? 'b-warn' : 'b-ok' }}">{{ $site->pending_updates }} offen</span></td></tr>
                  </tbody>
                </table>
              </div>

            @elseif($tab === 'licenses')
              <table class="tbl">
                <thead><tr><th>Lizenz</th><th>Gültig bis</th><th>Status</th></tr></thead>
                <tbody>
                  @forelse($site->licenses as $l)
                  <tr>
                    <td style="font-weight:600">{{ $l->product_name ?? '—' }}</td>
                    <td class="muted">{{ $l->expires_at ? $l->expires_at->format('d.m.Y') : '–' }}</td>
                    <td>
                      @if(!$l->expires_at)
                        <span class="badge b-neutral">unbegrenzt</span>
                      @elseif($l->expires_at->isPast())
                        <span class="badge b-crit">abgelaufen</span>
                      @elseif($l->expires_at->diffInDays(now(), false) <= 30)
                        <span class="badge b-warn">bald fällig</span>
                      @else
                        <span class="badge b-ok">aktiv</span>
                      @endif
                    </td>
                  </tr>
                  @empty
                  <tr><td colspan="3" style="text-align:center;color:var(--faint);padding:32px">Keine Lizenzen hinterlegt.</td></tr>
                  @endforelse
                </tbody>
              </table>
            @endif
          </div>

        </div>

        {{-- Rechte Spalte: Preview + Schnellaktionen --}}
        <div style="display:flex;flex-direction:column;gap:18px">
          <div class="preview">
            <div class="pv-bar">
              <div class="pdots"><i style="background:#fb4d63"></i><i style="background:#fbbf24"></i><i style="background:#34d399"></i></div>
              <div class="pv-url">{{ $site->url }}</div>
              <span class="badge {{ $site->status->value === 'online' ? 'b-ok' : 'b-crit' }}" style="font-size:10px">
                {{ $site->status->value === 'online' ? '● Online' : '● Offline' }}
              </span>
            </div>
            <div class="pv-img">
              <span class="ti ti-photo"></span>
              <div style="position:absolute;bottom:12px;font-size:11px;color:var(--faint)">Kein Screenshot verfügbar</div>
            </div>
          </div>

          <div class="card">
            <div class="sec-h"><span class="ti ti-bolt"></span><h3>Schnellaktionen</h3></div>
            <div class="qact">
              <a href="{{ $site->url }}" target="_blank" rel="noopener" class="btn ghost" style="justify-content:flex-start">
                <span class="ti ti-player-play"></span>Website öffnen
              </a>
              <button class="btn ghost" style="justify-content:flex-start">
                <span class="ti ti-database-export"></span>Backup auslösen
              </button>
              <button class="btn ghost" style="justify-content:flex-start">
                <span class="ti ti-refresh"></span>Updates einspielen
              </button>
              <button class="btn ghost" style="justify-content:flex-start">
                <span class="ti ti-lock-check"></span>SSL prüfen
              </button>
              <a href="{{ route('cockpit.domains') }}" class="btn ghost" style="justify-content:flex-start">
                <span class="ti ti-world"></span>Domain-Status
              </a>
            </div>
          </div>
        </div>

      </div>
      @else
        <div class="stub"><h3>Keine Websites hinterlegt</h3><p>Füge eine Website zu diesem Kunden hinzu.</p></div>
      @endif
    @endif
  </div>

</div>
</div>
