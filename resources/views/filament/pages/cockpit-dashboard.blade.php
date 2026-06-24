<x-filament-panels::page>
    <div class="oc">

        <div style="display:flex;align-items:center;gap:10px;margin:-6px 0 -4px">
            <span style="font-size:12px;color:var(--oc-text-faint)">Stand {{ $updatedAt }}</span>
        </div>

        {{-- ============================ HERO ============================ --}}
        <section class="oc-hero">
            {{-- Health --}}
            <div class="oc-card oc-health">
                <div class="oc-ring">
                    <svg width="96" height="96" viewBox="0 0 96 96">
                        <circle cx="48" cy="48" r="42" fill="none" stroke="rgba(255,255,255,.08)" stroke-width="8"/>
                        <circle cx="48" cy="48" r="42" fill="none" stroke="{{ $healthColor }}" stroke-width="8"
                                stroke-linecap="round" stroke-dasharray="{{ $ringC }}"
                                stroke-dashoffset="{{ $ringC - $healthDash }}" transform="rotate(-90 48 48)"/>
                    </svg>
                    <div class="oc-ring-mid"><b>{{ $healthPct }}%</b><span>gesund</span></div>
                </div>
                <div>
                    <h2><span class="oc-pulse {{ $tone === 'warn' ? 'warn' : ($tone === 'crit' ? 'crit' : '') }}"></span> {{ $healthLabel }}</h2>
                    <p>{{ $healthText }}</p>
                    <div class="oc-legend">
                        <div><i style="background:var(--oc-emerald)"></i> {{ $online }} Online</div>
                        @if ($maintenance > 0)<div><i style="background:var(--oc-amber)"></i> {{ $maintenance }} Wartung</div>@endif
                        @if ($offline > 0)<div><i style="background:var(--oc-rose)"></i> {{ $offline }} Offline</div>@endif
                        @if ($unknown > 0)<div><i style="background:rgba(255,255,255,.3)"></i> {{ $unknown }} Unbekannt</div>@endif
                    </div>
                </div>
            </div>

            {{-- KI-Assistenz --}}
            <div class="oc-card oc-ki">
                <div class="oc-ki-h">
                    <span class="oc-ki-ic">✦</span> KI-Assistenz
                    <span class="oc-ki-tag">{{ count($insights) }} {{ $aiActive ? 'Vorschläge · EU' : 'Hinweise · regelbasiert' }}</span>
                </div>
                @forelse ($insights as $i)
                    <div class="oc-insight"><span class="oc-dot {{ $i['dot'] }}"></span><div>{!! $i['html'] !!}</div></div>
                @empty
                    <div class="oc-ki-empty"><span class="oc-dot" style="background:var(--oc-emerald)"></span> Alles ruhig — derzeit keine offenen Hinweise.</div>
                @endforelse
                <div style="display:flex;gap:8px;margin-top:auto;padding-top:4px">
                    <a href="{{ $tasksUrl }}" class="fi-btn" style="font-size:12px;font-weight:600;padding:6px 12px;border-radius:8px;border:1px solid var(--oc-line-strong);color:var(--oc-text);text-decoration:none">Aufgaben öffnen</a>
                    @unless ($aiActive)
                        <a href="{{ \App\Filament\Pages\Settings::getUrl() }}" style="font-size:12px;font-weight:600;padding:6px 12px;color:var(--oc-text-dim);text-decoration:none">KI-Provider einrichten →</a>
                    @endunless
                </div>
            </div>
        </section>

        {{-- ============================ KPIs ============================ --}}
        <section class="oc-kpis">
            @foreach ($kpis as $k)
                <{{ $k['href'] ? 'a' : 'div' }} @if($k['href']) href="{{ $k['href'] }}" @endif class="oc-card oc-kpi {{ $k['edge'] }}">
                    <div class="oc-lab"><span style="font-size:14px;opacity:.8">{{ $k['icon'] }}</span> {{ $k['label'] }}</div>
                    <div class="oc-val">{{ $k['value'] }}@if($k['suffix'])<small> {{ $k['suffix'] }}</small>@endif</div>
                    <span class="oc-sub {{ $k['subTone'] }}">{{ $k['sub'] }}</span>
                    @if ($k['spark'])
                        <svg class="oc-spark" viewBox="0 0 96 38" preserveAspectRatio="none">
                            <polyline fill="none" stroke="{{ $k['spark']['color'] }}" stroke-width="2"
                                      stroke-linejoin="round" stroke-linecap="round" points="{{ $k['spark']['points'] }}"/>
                        </svg>
                    @endif
                </{{ $k['href'] ? 'a' : 'div' }}>
            @endforeach
        </section>

        {{-- ======================= HANDLUNG + RECHTS ======================= --}}
        <section class="oc-lower">

            {{-- Handlungs-Queue --}}
            <div class="oc-card">
                <div class="oc-sec-h">
                    <h3>Braucht Handlung</h3>
                    <span class="oc-count">{{ $openTasks }}</span>
                    <a href="{{ $tasksUrl }}" class="oc-more">Alle Aufgaben →</a>
                </div>
                @forelse ($queue as $row)
                    <{{ $row['href'] ? 'a' : 'div' }} @if($row['href']) href="{{ $row['href'] }}" @endif class="oc-q-row">
                        <div class="oc-sev {{ $row['cls'] }}">{{ $row['glyph'] }}</div>
                        <div>
                            <div class="oc-q-title">{{ $row['title'] }}</div>
                            <div class="oc-q-site">{{ $row['site'] }}</div>
                        </div>
                        <span class="oc-pill {{ $row['cls'] }}">{{ $row['pill'] }}</span>
                        <div class="oc-q-meta">
                            @if($row['age'])<b>{{ $row['age'] }}</b>@endif
                            {{ $row['overdue'] ? 'überfällig' : 'erkannt' }}
                        </div>
                    </{{ $row['href'] ? 'a' : 'div' }}>
                @empty
                    <div class="oc-empty">
                        <div class="oc-empty-ic">✓</div>
                        <p>Alles erledigt</p>
                        <small>Keine offenen Aufgaben — schön ruhig hier.</small>
                    </div>
                @endforelse
            </div>

            {{-- Rechte Spalte: Donut + Abläufe --}}
            <div class="oc-rstack">

                <div class="oc-card">
                    <div class="oc-sec-h"><h3>Status-Verteilung</h3></div>
                    <div class="oc-donut-wrap">
                        <div class="oc-donut">
                            <svg width="120" height="120" viewBox="0 0 120 120">
                                <circle cx="60" cy="60" r="50" fill="none" stroke="rgba(255,255,255,.06)" stroke-width="13"/>
                                @foreach ($donut['segments'] as $s)
                                    @if ($s['show'])
                                        <circle cx="60" cy="60" r="50" fill="none" stroke="{{ $s['color'] }}" stroke-width="13"
                                                stroke-dasharray="{{ $s['dash'] }} {{ $s['gap'] }}" stroke-dashoffset="{{ $s['offset'] }}"
                                                transform="rotate(-90 60 60)" stroke-linecap="butt"/>
                                    @endif
                                @endforeach
                            </svg>
                            <div class="oc-donut-mid"><b>{{ $donut['total'] }}</b><span>Sites</span></div>
                        </div>
                        <div class="oc-dlegend">
                            @foreach ($donut['segments'] as $s)
                                @if ($s['value'] > 0)
                                    <div class="oc-dl"><i style="background:{{ $s['color'] }}"></i> {{ $s['label'] }} <b>{{ $s['value'] }}</b></div>
                                @endif
                            @endforeach
                            @if ($donut['total'] === 0)
                                <div class="oc-dl">Noch keine Sites erfasst.</div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="oc-card">
                    <div class="oc-sec-h"><h3>Nächste Abläufe</h3><a href="{{ $tasksUrl }}" class="oc-more">Sites →</a></div>
                    @forelse ($expiries as $e)
                        <a href="{{ $e['href'] }}" class="oc-erow">
                            <div class="oc-etype" style="font-size:9px;font-weight:700;letter-spacing:.03em">{{ $e['tag'] }}</div>
                            <div>
                                <div class="oc-ename">{{ $e['name'] }}</div>
                                <div class="oc-esub">{{ $e['sub'] }}</div>
                            </div>
                            <div class="oc-ebar">
                                <div class="oc-edays {{ $e['tone'] }}">{{ $e['days'] }} Tage</div>
                                <div class="oc-etrack"><i style="width:{{ $e['pct'] }}%;background:{{ $e['color'] }}"></i></div>
                            </div>
                        </a>
                    @empty
                        <div class="oc-empty"><p style="font-size:13px">Keine baldigen Abläufe</p><small>In den nächsten 90 Tagen läuft nichts ab.</small></div>
                    @endforelse
                </div>

            </div>
        </section>

    </div>
</x-filament-panels::page>
