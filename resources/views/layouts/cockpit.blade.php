<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ config('app.name', 'WebOps') }}</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.24.0/dist/tabler-icons.min.css" rel="stylesheet">
<link href="{{ asset('css/cockpit.css') }}?v={{ @filemtime(public_path('css/cockpit.css')) }}" rel="stylesheet">
@livewireStyles
</head>
<body>

<div class="app">

  {{-- ===== Rail (Sidebar) ===== --}}
  <aside class="rail">

    {{-- Brand --}}
    <a href="{{ route('cockpit.dashboard') }}" class="brand" wire:navigate>
      <img class="brand-logo" src="{{ asset('img/brand-icon.svg') }}" alt="WebOps">
      <div class="brand-name">Web<span>Ops</span></div>
    </a>

    {{-- Navigation --}}
    <nav class="nav" aria-label="Hauptnavigation">

      <a href="{{ route('cockpit.dashboard') }}"
         class="nav-item {{ request()->routeIs('cockpit.dashboard') ? 'active' : '' }}">
        <span class="ti ti-layout-dashboard"></span>
        <span>Dashboard</span>
        @if(($criticalCount ?? 0) > 0)
          <span class="nav-badge" aria-label="{{ $criticalCount }} kritische Probleme">{{ $criticalCount }}</span>
        @endif
      </a>

      <a href="{{ route('cockpit.tasks') }}"
         class="nav-item {{ request()->routeIs('cockpit.tasks') ? 'active' : '' }}">
        <span class="ti ti-checklist"></span>
        <span>Aufgaben</span>
        @if(($openTaskCount ?? 0) > 0)
          <span class="nav-badge warn" aria-label="{{ $openTaskCount }} offene Aufgaben">{{ $openTaskCount }}</span>
        @endif
      </a>

      <div class="rail-sep"></div>
      <div class="nav-section-label">Überwachung</div>

      <a href="{{ route('cockpit.kunden') }}"
         class="nav-item {{ request()->routeIs('cockpit.kunden*') ? 'active' : '' }}">
        <span class="ti ti-building-store"></span>
        <span>Kunden</span>
      </a>

      <a href="{{ route('cockpit.seiten') }}"
         class="nav-item {{ request()->routeIs('cockpit.seiten') ? 'active' : '' }}">
        <span class="ti ti-world"></span>
        <span>Websites</span>
        @if(($problemSites ?? 0) > 0)
          <span class="nav-badge" aria-label="{{ $problemSites }} Problem-Sites">{{ $problemSites }}</span>
        @endif
      </a>

      <a href="{{ route('cockpit.domains') }}"
         class="nav-item {{ request()->routeIs('cockpit.domains') ? 'active' : '' }}">
        <span class="ti ti-certificate"></span>
        <span>Domains & SSL</span>
        @if(($domainAlerts ?? 0) > 0)
          <span class="nav-badge warn">{{ $domainAlerts }}</span>
        @endif
      </a>

      <div class="rail-sep"></div>
      <div class="nav-section-label">Berichte</div>

      <a href="{{ route('cockpit.berichte') }}"
         class="nav-item {{ request()->routeIs('cockpit.berichte') ? 'active' : '' }}">
        <span class="ti ti-chart-bar"></span>
        <span>Berichte</span>
      </a>

      <div class="rail-sep"></div>
      <div class="nav-section-label">System</div>

      <a href="{{ route('cockpit.benutzer') }}"
         class="nav-item {{ request()->routeIs('cockpit.benutzer') ? 'active' : '' }}">
        <span class="ti ti-users"></span>
        <span>Benutzer</span>
      </a>

      <a href="{{ route('cockpit.einstellungen') }}"
         class="nav-item {{ request()->routeIs('cockpit.einstellungen') ? 'active' : '' }}">
        <span class="ti ti-settings"></span>
        <span>Einstellungen</span>
      </a>

    </nav>

    {{-- User Footer --}}
    <div class="rail-footer">
      <div class="user-pill">
        @php
          $u = auth()->user();
          $colors = ['#0EA5E9','#10B981','#A855F7','#F59E0B','#EF4444'];
          $col = $colors[crc32($u->name ?? '') % 5];
        @endphp
        <div class="user-av" style="background:{{ $col }}">{{ strtoupper(substr($u->name ?? 'U', 0, 2)) }}</div>
        <div class="user-info">
          <div class="user-name">{{ $u->name ?? 'Benutzer' }}</div>
          <div class="user-role">{{ $u->role ?? 'admin' }}</div>
        </div>
        <form method="POST" action="{{ route('filament.admin.auth.logout') }}" style="margin-left:auto;line-height:0">
          @csrf
          <button type="submit" class="ti ti-logout" style="font-size:15px;color:var(--faint);background:none;border:none;cursor:pointer;padding:0" title="Abmelden" aria-label="Abmelden"></button>
        </form>
      </div>
    </div>

  </aside>

  {{-- ===== Stage (Main Content) ===== --}}
  <main class="stage" id="main-content">
    {{ $slot }}
  </main>

</div>

@livewireScripts
</body>
</html>
