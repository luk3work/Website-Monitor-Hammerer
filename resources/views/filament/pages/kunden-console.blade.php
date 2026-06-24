<x-filament-panels::page>
@php
    $pal = ['#d7263d','#0ea5e9','#10b981','#a855f7','#f59e0b','#14b8a6'];
    $dotFor = fn ($st) => $st === \App\Enums\SiteStatus::Online ? 'ok' : ($st === \App\Enums\SiteStatus::Maintenance ? 'warn' : ($st === \App\Enums\SiteStatus::Offline ? 'crit' : 'off'));
    $sevLabel = fn ($s) => $s === 'crit' ? 'Kritisch' : ($s === 'warn' ? 'Warnung' : ($s === 'info' ? 'Info' : 'OK'));
    $customer = $this->currentCustomer();
    $site = $this->currentSite();
@endphp

<div class="oc-md">

    {{-- ===================== Spalte 2: Kundenliste ===================== --}}
    <div class="oc-clist">
        <div class="oc-ch">
            <div class="oc-srch">
                <span style="color:var(--oc-text-faint)">⌕</span>
                <input type="text" placeholder="Kunde suchen …" wire:model.live.debounce.300ms="search">
            </div>
            <div class="oc-filters">
                <button class="oc-chip {{ $filter === 'all' ? 'on' : '' }}" wire:click="setFilter('all')">Alle</button>
                <button class="oc-chip {{ $filter === 'problems' ? 'on' : '' }}" wire:click="setFilter('problems')">Probleme</button>
                <button class="oc-chip {{ $filter === 'critical' ? 'on' : '' }}" wire:click="setFilter('critical')">Kritisch</button>
            </div>
        </div>
        <div class="oc-citems">
            @forelse ($this->customersList() as $c)
                @php $worst = $this->customerWorst($c); $cnt = $this->customerIssues($c)->count(); $col = $pal[$c->id % count($pal)]; @endphp
                <button class="oc-citem {{ $customerId === $c->id ? 'on' : '' }}" wire:click="selectCustomer({{ $c->id }})">
                    <span class="oc-logo" style="background:{{ $col }}">{{ mb_substr($c->name, 0, 1) }}</span>
                    <span style="min-width:0;flex:1">
                        <span class="oc-cn"><span class="oc-d {{ $dotFor(optional($c->sites->first())->status) }}"></span>{{ $c->name }}</span>
                        <span class="oc-cs">{{ $c->sites->count() }} Website{{ $c->sites->count() === 1 ? '' : 's' }}@if($c->company) · {{ $c->company }}@endif</span>
                    </span>
                    <span class="oc-cb">
                        @if($worst !== 'ok')
                            <span class="oc-bdg {{ $worst }}">{{ $cnt }}</span>
                        @else
                            <span style="color:var(--oc-emerald);font-weight:700">✓</span>
                        @endif
                    </span>
                </button>
            @empty
                <div class="oc-emptybox"><p>Keine Kunden gefunden</p><small>Suche/Filter anpassen.</small></div>
            @endforelse
        </div>
    </div>

    {{-- ===================== Spalte 3: Detail ===================== --}}
    <div class="oc-detail">
        @if (! $customer)
            <div class="oc-emptybox" style="padding-top:120px"><div class="ic">☖</div><p>Kein Kunde ausgewählt</p><small>Wähle links einen Kunden.</small></div>
        @else
            @php $col = $pal[$customer->id % count($pal)]; $cWorst = $this->customerWorst($customer); $cCnt = $this->customerIssues($customer)->count(); @endphp

            <div class="oc-dhead">
                <div class="oc-big" style="background:{{ $col }}">{{ mb_substr($customer->name, 0, 1) }}</div>
                <div style="min-width:0">
                    <h2>{{ $customer->name }}
                        @if($cWorst !== 'ok')<span class="oc-bdg {{ $cWorst }}">{{ $sevLabel($cWorst) }} · {{ $cCnt }}</span>
                        @else<span class="oc-bdg ok">Alles ok</span>@endif
                    </h2>
                    <div class="oc-dsub">
                        @if($customer->company)<span>{{ $customer->company }}</span>@endif
                        @if($customer->email)<span>· {{ $customer->email }}</span>@endif
                        @if($customer->sites->isNotEmpty())
                            <span class="oc-sitesel">
                                <span style="color:var(--oc-text-faint)">◵</span>
                                <select wire:model.live="siteId">
                                    @foreach($customer->sites as $s)
                                        <option value="{{ $s->id }}">{{ $s->label ?? $s->url }}</option>
                                    @endforeach
                                </select>
                            </span>
                        @endif
                    </div>
                </div>
                <div class="oc-dact">
                    @if($site)<a class="oc-btn ghost" href="{{ $site->url }}" target="_blank" rel="noopener">↗ Website</a>@endif
                    <a class="oc-btn acc" href="{{ \App\Filament\Resources\CustomerResource::getUrl('edit', ['record' => $customer]) }}">Bearbeiten</a>
                </div>
            </div>

            @if (! $site)
                <div class="oc-emptybox" style="padding-top:80px"><p>Keine Website hinterlegt</p><small>Für diesen Kunden ist noch keine Site verbunden.</small></div>
            @else
                @php
                    $sslDays = $this->daysUntil($site->ssl_expires_at);
                    $domDays = $this->daysUntil($site->domain_expires_at);
                    $upd = (int) $site->pending_updates;
                    $pluginUpd = $site->plugins->where('update_available', true)->count();
                @endphp

                <div class="oc-dbody">
                    <div style="display:flex;flex-direction:column;gap:18px;min-width:0">

                        {{-- Health-Karten --}}
                        <div class="oc-health2">
                            <div class="oc-hcard"><div class="hl">Status</div>
                                <div class="hv"><span class="oc-d {{ $dotFor($site->status) }}"></span>{{ $site->status?->label() ?? '—' }}</div>
                                <div class="hs">Letzter Check: {{ $site->last_seen_at?->diffForHumans() ?? 'unbekannt' }}</div></div>
                            <div class="oc-hcard"><div class="hl">SSL-Zertifikat</div>
                                <div class="hv"><span class="oc-d {{ $sslDays === null ? 'off' : ($sslDays <= 7 ? 'crit' : ($sslDays <= 30 ? 'warn' : 'ok')) }}"></span>{{ $sslDays === null ? 'unbekannt' : $sslDays.' Tage' }}</div>
                                <div class="hs">{{ $site->ssl_expires_at?->format('d.m.Y') ?? 'kein Ablaufdatum' }}</div></div>
                            <div class="oc-hcard"><div class="hl">Updates</div>
                                <div class="hv"><span class="oc-d {{ $upd ? 'warn' : 'ok' }}"></span>{{ $upd }} offen</div>
                                <div class="hs">{{ $pluginUpd }} Plugins betroffen</div></div>
                            <div class="oc-hcard"><div class="hl">Paket</div>
                                <div class="hv">{{ $site->package_tier ?? '—' }}</div>
                                <div class="hs">{{ $site->cms_type ?? 'CMS' }} · {{ $site->wp_version ?? '?' }}</div></div>
                        </div>

                        {{-- Tabs --}}
                        <div class="oc-tabs">
                            @foreach(['overview' => 'Übersicht', 'updates' => 'Updates', 'dns' => 'Domain & SSL', 'plugins' => 'Plugins', 'licenses' => 'Lizenzen', 'monitoring' => 'Monitoring'] as $key => $lbl)
                                <button class="oc-tab {{ $tab === $key ? 'on' : '' }}" wire:click="setTab('{{ $key }}')">{{ $lbl }}</button>
                            @endforeach
                        </div>

                        <div class="oc-card">
                            @if($tab === 'overview')
                                <div style="padding:16px"><div class="oc-kv">
                                    <div class="cell"><div class="k">URL</div><div class="v">{{ $site->url }}</div></div>
                                    <div class="cell"><div class="k">Domain</div><div class="v"><span class="oc-d {{ $domDays === null ? 'off' : ($domDays <= 30 ? 'warn' : 'ok') }}"></span>{{ $domDays === null ? 'unbekannt' : 'in '.$domDays.' Tagen' }}</div></div>
                                    <div class="cell"><div class="k">CMS</div><div class="v">{{ $site->cms_type ?? '—' }} {{ $site->wp_version }}</div></div>
                                    <div class="cell"><div class="k">PHP</div><div class="v">{{ $site->php_version ?? '—' }}</div></div>
                                    <div class="cell"><div class="k">Plugins</div><div class="v">{{ $site->plugins->count() }} · {{ $pluginUpd }} Updates</div></div>
                                    <div class="cell"><div class="k">Hosting bei uns</div><div class="v">{{ $site->hosted_by_us ? 'ja' : 'nein' }}</div></div>
                                    <div class="cell"><div class="k">Paket</div><div class="v">{{ $site->package_tier ?? '—' }}</div></div>
                                    <div class="cell"><div class="k">Letzter Check</div><div class="v">{{ $site->last_seen_at?->diffForHumans() ?? 'unbekannt' }}</div></div>
                                </div></div>

                            @elseif($tab === 'updates')
                                @php $updPlugins = $site->plugins->where('update_available', true); @endphp
                                @if($updPlugins->isEmpty())
                                    <div class="oc-emptybox"><div class="ic" style="color:var(--oc-emerald)">✓</div><p>Alles aktuell</p><small>Keine ausstehenden Plugin-Updates.</small></div>
                                @else
                                    <table class="oc-tbl"><thead><tr><th>Plugin</th><th>Installiert</th><th>Verfügbar</th><th>Status</th></tr></thead><tbody>
                                        @foreach($updPlugins as $p)
                                            <tr><td style="font-weight:600">{{ $p->name ?? $p->slug }}</td><td style="color:var(--oc-text-dim)">{{ $p->version ?? '—' }}</td>
                                            <td>{{ $p->update_version ?? '—' }}</td><td><span class="oc-bdg warn">Update</span></td></tr>
                                        @endforeach
                                    </tbody></table>
                                @endif

                            @elseif($tab === 'dns')
                                <div style="padding:16px"><div class="oc-kv">
                                    <div class="cell"><div class="k">Domain</div><div class="v">{{ $site->url }}</div></div>
                                    <div class="cell"><div class="k">Domain bei uns</div><div class="v">{{ $site->domain_by_us ? 'ja' : 'nein' }}</div></div>
                                    <div class="cell"><div class="k">Domain-Ablauf</div><div class="v"><span class="oc-d {{ $domDays === null ? 'off' : ($domDays <= 30 ? 'warn' : 'ok') }}"></span>{{ $site->domain_expires_at?->format('d.m.Y') ?? 'unbekannt' }}</div></div>
                                    <div class="cell"><div class="k">SSL gültig bis</div><div class="v"><span class="oc-d {{ $sslDays === null ? 'off' : ($sslDays <= 7 ? 'crit' : ($sslDays <= 30 ? 'warn' : 'ok')) }}"></span>{{ $site->ssl_expires_at?->format('d.m.Y') ?? 'unbekannt' }}</div></div>
                                    <div class="cell"><div class="k">SSL verbleibend</div><div class="v">{{ $sslDays === null ? '—' : $sslDays.' Tage' }}</div></div>
                                    <div class="cell"><div class="k">Hosting</div><div class="v">{{ $site->hosted_by_us ? 'bei uns' : 'extern' }}</div></div>
                                </div></div>

                            @elseif($tab === 'plugins')
                                @if($site->plugins->isEmpty())
                                    <div class="oc-emptybox"><p>Keine Plugins erfasst</p><small>Sobald der Reporter Daten sendet, erscheinen sie hier.</small></div>
                                @else
                                    <table class="oc-tbl"><thead><tr><th>Plugin</th><th>Version</th><th>Aktiv</th><th>Status</th></tr></thead><tbody>
                                        @foreach($site->plugins->sortByDesc('update_available') as $p)
                                            <tr><td style="font-weight:600">{{ $p->name ?? $p->slug }}</td><td style="color:var(--oc-text-dim)">{{ $p->version ?? '—' }}</td>
                                            <td>{{ $p->active ? 'ja' : 'nein' }}</td>
                                            <td>@if($p->update_available)<span class="oc-bdg warn">Update</span>@else<span class="oc-bdg ok">aktuell</span>@endif</td></tr>
                                        @endforeach
                                    </tbody></table>
                                @endif

                            @elseif($tab === 'licenses')
                                @if($site->licenses->isEmpty())
                                    <div class="oc-emptybox"><p>Keine Lizenzen hinterlegt</p><small>Lizenzen können in der Kundenpflege ergänzt werden.</small></div>
                                @else
                                    <table class="oc-tbl"><thead><tr><th>Lizenz</th><th>Gültig bis</th><th>Status</th></tr></thead><tbody>
                                        @foreach($site->licenses as $l)
                                            @php $ld = $this->daysUntil($l->expires_at); @endphp
                                            <tr><td style="font-weight:600">{{ $l->name ?? '—' }}</td>
                                            <td style="color:var(--oc-text-dim)">{{ $l->expires_at?->format('d.m.Y') ?? '—' }}</td>
                                            <td>@if($ld !== null && $ld <= 21)<span class="oc-bdg warn">in {{ $ld }} Tagen</span>@else<span class="oc-bdg ok">aktiv</span>@endif</td></tr>
                                        @endforeach
                                    </tbody></table>
                                @endif

                            @else
                                <div class="oc-emptybox" style="padding:34px 20px">
                                    <div class="ic">◔</div>
                                    <p>Formulare · Security · Backups</p>
                                    <small style="display:block;max-width:360px;margin:6px auto 0">Diese Module werden vom Reporter-Plugin gespeist. Sobald Formular-Checks, Security-Scans und Backup-Status gemeldet werden, erscheinen sie hier — ohne Platzhalterdaten.</small>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- Rechte Spalte: Vorschau + Schnellaktionen --}}
                    <div style="display:flex;flex-direction:column;gap:18px">
                        <div class="oc-preview">
                            <div class="oc-pvbar"><div class="pvd"><i style="background:#fb4d63"></i><i style="background:#fbbf24"></i><i style="background:#34d399"></i></div><div class="oc-pvurl">{{ $site->url }}</div></div>
                            <div class="oc-pvframe">
                                <iframe src="{{ $site->url }}" loading="lazy" tabindex="-1" sandbox="allow-scripts allow-same-origin" title="Vorschau {{ $site->url }}"></iframe>
                            </div>
                        </div>
                        <div class="oc-card">
                            <div class="oc-sec-h"><h3>Schnellaktionen</h3></div>
                            <div style="padding:13px 15px;display:flex;flex-direction:column;gap:8px">
                                <a class="oc-btn ghost" style="justify-content:flex-start" href="{{ \App\Filament\Resources\SiteResource::getUrl('view', ['record' => $site]) }}">▤ Volle Site-Ansicht</a>
                                @if($site->url)<a class="oc-btn ghost" style="justify-content:flex-start" href="{{ rtrim($site->url, '/') }}/wp-admin" target="_blank" rel="noopener">⌂ Backend-Login</a>@endif
                                <a class="oc-btn ghost" style="justify-content:flex-start" href="{{ \App\Filament\Resources\SiteResource::getUrl('edit', ['record' => $site]) }}">⚙ Site bearbeiten</a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    </div>
</div>
</x-filament-panels::page>
