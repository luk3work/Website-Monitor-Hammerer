<x-filament-panels::page>
@php
    $rows    = $this->rows();
    $summary = $this->summary();
    $toneLabel = fn($t) => match($t) { 'crit' => 'Kritisch', 'warn' => 'Bald fällig', 'ok' => 'OK', default => '–' };
    $daysStr   = fn($d) => $d === null ? '–' : ($d < 0 ? abs($d).'d abgelaufen' : $d.'d');
@endphp

<div class="oc" style="padding-bottom:40px">

    {{-- ========= Summary-Chips ========= --}}
    <div class="oc-dm-summary">
        <div class="oc-dm-chip neutral">
            <span class="num">{{ $summary['total'] }}</span>
            <span class="lbl">Gesamt</span>
        </div>
        <div class="oc-dm-chip crit">
            <span class="num">{{ $summary['critical'] }}</span>
            <span class="lbl">Kritisch</span>
        </div>
        <div class="oc-dm-chip warn">
            <span class="num">{{ $summary['warning'] }}</span>
            <span class="lbl">Bald fällig</span>
        </div>
        <div class="oc-dm-chip ok">
            <span class="num">{{ $summary['ok'] }}</span>
            <span class="lbl">Alles OK</span>
        </div>

        <div class="oc-dm-spacer"></div>

        {{-- Filter-Chips --}}
        <button wire:click="setFilter('all')"      class="oc-chip {{ $filter === 'all'      ? 'on' : '' }}">Alle</button>
        <button wire:click="setFilter('critical')" class="oc-chip {{ $filter === 'critical' ? 'on' : '' }}">Kritisch</button>
        <button wire:click="setFilter('warning')"  class="oc-chip {{ $filter === 'warning'  ? 'on' : '' }}">Warnung</button>
        <button wire:click="setFilter('ok')"       class="oc-chip {{ $filter === 'ok'       ? 'on' : '' }}">OK</button>

        {{-- Search --}}
        <div class="oc-srch" style="margin-left:8px;width:240px">
            <span style="color:var(--oc-text-faint)">⌕</span>
            <input type="text" placeholder="Domain suchen …" wire:model.live.debounce.250ms="search">
        </div>
    </div>

    {{-- ========= Domains-Tabelle ========= --}}
    <div class="oc-card">
        <div class="oc-sec-h">
            <h3>Domains &amp; SSL-Zertifikate</h3>
            <span class="oc-count">{{ $rows->count() }}</span>
        </div>

        <table class="oc-tbl" style="width:100%">
            <thead>
                <tr>
                    <th style="width:36px"></th>
                    <th>Website</th>
                    <th>Kunde</th>
                    <th>URL</th>
                    <th>SSL-Status</th>
                    <th>SSL läuft ab</th>
                    <th>Domain läuft ab</th>
                    <th>Domain</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($rows as $row)
                @php
                    $s       = $row['s'];
                    $sslDays = $row['sslDays'];
                    $domDays = $row['domDays'];
                    $sslTone = $row['sslTone'];
                    $domTone = $row['domTone'];
                    $worst   = $row['worst'];
                @endphp
                <tr class="oc-dm-row {{ $worst }}">
                    <td>
                        <div class="oc-dm-sev {{ $worst }}">
                            @if($worst === 'crit') ⚠ @elseif($worst === 'warn') ⚡ @else ✓ @endif
                        </div>
                    </td>
                    <td>
                        <div style="font-weight:600;font-size:13px">{{ $s->label }}</div>
                        @if($s->cms_version ?? $s->wp_version)
                            <div style="font-size:11px;color:var(--oc-text-faint);margin-top:2px">WP {{ $s->wp_version }}</div>
                        @endif
                    </td>
                    <td style="color:var(--oc-text-dim);font-size:12.5px">{{ $s->customer?->name ?? '—' }}</td>
                    <td>
                        <a href="{{ $s->url }}" target="_blank" rel="noopener"
                           style="color:var(--oc-text-dim);font-size:12px;text-decoration:none;font-family:monospace">
                            {{ parse_url($s->url, PHP_URL_HOST) ?? $s->url }}
                        </a>
                    </td>
                    <td>
                        <span class="oc-bdg {{ $sslTone }}">
                            @if($sslTone === 'crit') ✗ Kritisch
                            @elseif($sslTone === 'warn') ⚡ Bald fällig
                            @elseif($sslTone === 'ok') ✓ Gültig
                            @else – Unbekannt
                            @endif
                        </span>
                    </td>
                    <td>
                        @if($s->ssl_expires_at)
                            <span class="oc-edays {{ $sslTone }}">{{ $daysStr($sslDays) }}</span>
                            <div style="font-size:10.5px;color:var(--oc-text-faint);margin-top:2px">{{ $s->ssl_expires_at->format('d.m.Y') }}</div>
                        @else
                            <span style="color:var(--oc-text-faint)">–</span>
                        @endif
                    </td>
                    <td>
                        @if($s->domain_expires_at)
                            <span class="oc-edays {{ $domTone }}">{{ $daysStr($domDays) }}</span>
                            <div style="font-size:10.5px;color:var(--oc-text-faint);margin-top:2px">{{ $s->domain_expires_at->format('d.m.Y') }}</div>
                        @else
                            <span style="color:var(--oc-text-faint)">–</span>
                        @endif
                    </td>
                    <td>
                        @if($s->domain_by_us)
                            <span class="oc-bdg neutral">Bei uns</span>
                        @else
                            <span class="oc-bdg info">Extern</span>
                        @endif
                    </td>
                    <td>
                        <a href="{{ \App\Filament\Resources\SiteResource::getUrl('edit', ['record' => $s]) }}"
                           class="oc-btn ghost" style="font-size:11.5px;padding:5px 10px">Details</a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" style="padding:48px 20px;text-align:center;color:var(--oc-text-faint)">
                        Keine Domains gefunden — Filter oder Suche anpassen.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ========= Legenden-Karte ========= --}}
    <div class="oc-card" style="padding:18px 22px">
        <div style="display:flex;gap:28px;flex-wrap:wrap;align-items:center">
            <span style="font-size:12px;font-weight:700;color:var(--oc-text-dim);text-transform:uppercase;letter-spacing:.06em">Legende</span>
            <div style="display:flex;align-items:center;gap:8px;font-size:12.5px;color:var(--oc-text-dim)">
                <span class="oc-bdg crit">✗ Kritisch</span> Abgelaufen oder &lt; 14 Tage (SSL) / &lt; 30 Tage (Domain)
            </div>
            <div style="display:flex;align-items:center;gap:8px;font-size:12.5px;color:var(--oc-text-dim)">
                <span class="oc-bdg warn">⚡ Warnung</span> 14–45 Tage (SSL) / 30–90 Tage (Domain)
            </div>
            <div style="display:flex;align-items:center;gap:8px;font-size:12.5px;color:var(--oc-text-dim)">
                <span class="oc-bdg ok">✓ OK</span> Mehr als 45 Tage (SSL) / 90 Tage (Domain)
            </div>
        </div>
    </div>

</div>
</x-filament-panels::page>
