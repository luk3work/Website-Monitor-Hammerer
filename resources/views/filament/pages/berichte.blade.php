<x-filament-panels::page>
@php
    $kpis       = $this->kpis();
    $chart      = $this->snapshotChart();
    $taskTypes  = $this->tasksByType();
    $expiries   = $this->upcomingExpiries();
    $status     = $this->statusBreakdown();
    $chartMax   = max(1, collect($chart)->max('value'));
    $tagLabel   = ['SSL' => '🔒', 'DOM' => '🌐', 'LIZ' => '🔑'];
    $typeLabel  = ['update' => 'Updates', 'ssl' => 'SSL', 'domain' => 'Domain', 'security' => 'Sicherheit', 'form' => 'Formular', 'backup' => 'Backup', 'license' => 'Lizenz', 'heartbeat' => 'Heartbeat'];
@endphp

<div class="oc" style="padding-bottom:48px">

    {{-- ========= Zeitraum-Auswahl ========= --}}
    <div style="display:flex;align-items:center;gap:8px">
        <span style="font-size:12px;color:var(--oc-text-faint);font-weight:600;margin-right:4px">Zeitraum:</span>
        @foreach(['7' => '7 Tage', '30' => '30 Tage', '90' => '90 Tage', '365' => '12 Monate'] as $val => $lbl)
            <button wire:click="setPeriod('{{ $val }}')"
                    class="oc-chip {{ $period === $val ? 'on' : '' }}">{{ $lbl }}</button>
        @endforeach
    </div>

    {{-- ========= KPI-Reihe ========= --}}
    <div class="oc-kpis" style="grid-template-columns:repeat(4,1fr)">
        <div class="oc-card oc-kpi">
            <div class="oc-lab">🏢 Kunden</div>
            <div class="oc-val">{{ $kpis['totalCustomers'] }}</div>
            <span class="oc-sub neutral">gesamt</span>
        </div>
        <div class="oc-card oc-kpi">
            <div class="oc-lab">🌐 Websites</div>
            <div class="oc-val">{{ $kpis['totalSites'] }}</div>
            <span class="oc-sub neutral">aktiv</span>
        </div>
        <div class="oc-card oc-kpi {{ $kpis['tasksOpened'] > 0 ? 'warnedge' : '' }}">
            <div class="oc-lab">📋 Aufgaben ({{ $period }}d)</div>
            <div class="oc-val">{{ $kpis['tasksOpened'] }}</div>
            <span class="oc-sub {{ $kpis['tasksClosed'] >= $kpis['tasksOpened'] ? 'emerald' : 'amber' }}">
                {{ $kpis['tasksClosed'] }} erledigt
            </span>
        </div>
        <div class="oc-card oc-kpi {{ $kpis['sslCrit'] > 0 ? 'alert' : '' }}">
            <div class="oc-lab">🔒 SSL / Domain kritisch</div>
            <div class="oc-val">{{ $kpis['sslCrit'] + $kpis['domCrit'] }}</div>
            <span class="oc-sub {{ ($kpis['sslCrit'] + $kpis['domCrit']) > 0 ? 'rose' : 'emerald' }}">
                Handlungsbedarf
            </span>
        </div>
    </div>

    {{-- ========= Charts Row ========= --}}
    <div class="oc-rep-row">

        {{-- Snapshot-Aktivität (Balkendiagramm) --}}
        <div class="oc-card" style="flex:2">
            <div class="oc-sec-h">
                <h3>Reporter-Aktivität (12 Monate)</h3>
                <span class="oc-count">{{ $kpis['snapshots'] }} Snapshots ({{ $period }}d)</span>
            </div>
            <div class="oc-barchart">
                @foreach ($chart as $m)
                    <div class="oc-baritem">
                        <div class="oc-barwrap">
                            <div class="oc-bar" style="height:{{ $m['pct'] }}%;background:var(--oc-accent)"></div>
                        </div>
                        <div class="oc-barlbl">{{ $m['label'] }}</div>
                        @if($m['value'] > 0)
                            <div class="oc-barval">{{ $m['value'] }}</div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Status-Verteilung --}}
        <div class="oc-card" style="flex:1">
            <div class="oc-sec-h"><h3>Status-Verteilung</h3></div>
            @php
                $total = collect($status)->sum('cnt');
                $circ  = 2 * pi() * 42;
                $off   = 0;
            @endphp
            <div style="padding:22px;display:flex;flex-direction:column;align-items:center;gap:18px">
                <svg width="120" height="120" viewBox="0 0 120 120">
                    <circle cx="60" cy="60" r="42" fill="none" stroke="rgba(255,255,255,.06)" stroke-width="13"/>
                    @foreach($status as $seg)
                        @if($seg['cnt'] > 0 && $total > 0)
                            @php
                                $dash = ($seg['cnt'] / $total) * $circ;
                                $gap  = $circ - $dash;
                            @endphp
                            <circle cx="60" cy="60" r="42" fill="none"
                                    stroke="{{ $seg['color'] }}" stroke-width="13"
                                    stroke-dasharray="{{ $dash }} {{ $gap }}"
                                    stroke-dashoffset="{{ $circ - $off }}"
                                    transform="rotate(-90 60 60)"/>
                            @php $off += $dash; @endphp
                        @endif
                    @endforeach
                </svg>
                <div style="display:flex;flex-direction:column;gap:9px;width:100%">
                    @foreach($status as $seg)
                        @if($seg['cnt'] > 0)
                        <div class="oc-dl">
                            <i style="background:{{ $seg['color'] }};border-radius:3px;flex:0 0 9px;height:9px"></i>
                            {{ $seg['label'] }}
                            <b>{{ $seg['cnt'] }}</b>
                        </div>
                        @endif
                    @endforeach
                    @if($total === 0)
                        <div class="oc-dl" style="color:var(--oc-text-faint)">Noch keine Sites.</div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Aufgaben nach Typ --}}
        <div class="oc-card" style="flex:1">
            <div class="oc-sec-h">
                <h3>Aufgaben nach Typ</h3>
                <span class="oc-count">{{ $period }}d</span>
            </div>
            @forelse($taskTypes as $tt)
                @php $max = collect($taskTypes)->max('cnt'); @endphp
                <div style="padding:10px 18px;border-bottom:1px solid var(--oc-line);display:flex;align-items:center;gap:12px">
                    <div style="font-size:12.5px;font-weight:600;width:90px;flex:0 0 90px;color:var(--oc-text-dim)">
                        {{ $typeLabel[$tt['type']] ?? ucfirst($tt['type']) }}
                    </div>
                    <div style="flex:1;height:6px;background:rgba(255,255,255,.07);border-radius:99px;overflow:hidden">
                        <div style="height:100%;width:{{ round(($tt['cnt']/$max)*100) }}%;background:var(--oc-accent);border-radius:99px"></div>
                    </div>
                    <div style="font-size:12px;font-weight:700;color:var(--oc-text);min-width:24px;text-align:right">{{ $tt['cnt'] }}</div>
                </div>
            @empty
                <div class="oc-empty" style="padding:24px">
                    <p>Keine Aufgaben in diesem Zeitraum.</p>
                </div>
            @endforelse
        </div>

    </div>

    {{-- ========= Ablauf-Timeline ========= --}}
    <div class="oc-card">
        <div class="oc-sec-h">
            <h3>Nächste Abläufe (90 Tage)</h3>
            <span class="oc-count">{{ count($expiries) }}</span>
        </div>

        @if(count($expiries) === 0)
            <div class="oc-empty"><div class="oc-empty-ic">✓</div><p>Keine Abläufe in den nächsten 90 Tagen.</p></div>
        @else
        <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:0;border-top:0">
            @foreach($expiries as $e)
            <a href="{{ $e['href'] }}" class="oc-erow" style="border-bottom:1px solid var(--oc-line)">
                <div class="oc-etype" style="font-size:16px">{{ $tagLabel[$e['tag']] ?? '📋' }}</div>
                <div style="flex:1;min-width:0">
                    <div class="oc-ename">{{ $e['name'] }}</div>
                    <div class="oc-esub">{{ $e['sub'] }} · {{ $e['tag'] }}</div>
                </div>
                <div class="oc-ebar">
                    <div class="oc-edays {{ $e['tone'] }}">
                        {{ $e['days'] < 0 ? 'ABGELAUFEN' : $e['days'].'d' }}
                    </div>
                    <div class="oc-etrack" style="width:80px">
                        @php $pct = max(2, min(100, 100 - (int)(($e['days'] / 90) * 100))); @endphp
                        <i style="width:{{ $pct }}%;background:{{ $e['tone'] === 'crit' ? 'var(--oc-rose)' : ($e['tone'] === 'warn' ? 'var(--oc-amber)' : 'var(--oc-emerald)') }}"></i>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
        @endif
    </div>

    {{-- ========= Updates-Übersicht ========= --}}
    <div class="oc-card">
        <div class="oc-sec-h">
            <h3>Ausstehende Updates</h3>
            <span class="oc-count">{{ $kpis['updates'] }} gesamt</span>
            <a href="{{ \App\Filament\Resources\SiteResource::getUrl('index') }}" class="oc-more">Alle Sites →</a>
        </div>
        @php
            $sitesWithUpdates = \App\Models\Site::query()
                ->where('is_archived', false)
                ->where('pending_updates', '>', 0)
                ->with('customer')
                ->orderByDesc('pending_updates')
                ->limit(10)
                ->get();
        @endphp
        @forelse($sitesWithUpdates as $su)
        <div class="oc-q-row" style="grid-template-columns:auto 1fr auto auto">
            <div class="oc-sev warn">↑</div>
            <div>
                <div class="oc-q-title">{{ $su->label }}</div>
                <div class="oc-q-site">{{ $su->customer?->name }} · WP {{ $su->wp_version ?? '–' }}</div>
            </div>
            <span class="oc-pill warn">{{ $su->pending_updates }} Updates</span>
            <a href="{{ \App\Filament\Resources\SiteResource::getUrl('edit', ['record' => $su]) }}"
               class="oc-btn ghost" style="font-size:11.5px;padding:5px 10px">Details</a>
        </div>
        @empty
        <div class="oc-empty"><div class="oc-empty-ic">✓</div><p>Alle Sites sind aktuell.</p></div>
        @endforelse
    </div>

</div>
</x-filament-panels::page>
