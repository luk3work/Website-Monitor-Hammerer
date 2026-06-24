<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ops Cockpit</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@3.24.0/dist/tabler-icons.min.css" rel="stylesheet">
<link href="{{ asset('css/cockpit.css') }}?v={{ filemtime(public_path('css/cockpit.css')) }}" rel="stylesheet">
@vite(['resources/js/app.js'])
@livewireStyles
</head>
<body>
<div class="app">

  {{-- ====== Sidebar ====== --}}
  <aside class="rail">
    <div class="brand">
      <div class="logo">O</div>
      <div>
        <b>Ops Cockpit</b>
        <span>Agentur-Control-Center</span>
      </div>
    </div>

    <nav class="nav">
      <div class="lbl">Überblick</div>
      <a href="{{ route('cockpit.dashboard') }}" class="{{ request()->routeIs('cockpit.dashboard') ? 'active' : '' }}">
        <span class="ti ti-layout-dashboard"></span><span>Dashboard</span>
        @if($criticalCount > 0)
          <span class="nbadge">{{ $criticalCount }}</span>
        @endif
      </a>

      <div class="lbl">Betrieb</div>
      <a href="{{ route('cockpit.kunden') }}" class="{{ request()->routeIs('cockpit.kunden') ? 'active' : '' }}">
        <span class="ti ti-users"></span><span>Kunden</span>
        @if($problemCustomers > 0)
          <span class="nbadge amber">{{ $problemCustomers }}</span>
        @endif
      </a>
      <a href="{{ route('cockpit.seiten') }}" class="{{ request()->routeIs('cockpit.seiten') ? 'active' : '' }}">
        <span class="ti ti-world-www"></span><span>Seiten</span>
      </a>
      <a href="{{ route('cockpit.domains') }}" class="{{ request()->routeIs('cockpit.domains') ? 'active' : '' }}">
        <span class="ti ti-world"></span><span>Domains</span>
        @if($domainAlerts > 0)
          <span class="nbadge">{{ $domainAlerts }}</span>
        @endif
      </a>

      <div class="lbl">Verwaltung</div>
      <a href="{{ route('cockpit.benutzer') }}" class="{{ request()->routeIs('cockpit.benutzer') ? 'active' : '' }}">
        <span class="ti ti-user-cog"></span><span>Benutzer</span>
      </a>
      <a href="{{ route('cockpit.berichte') }}" class="{{ request()->routeIs('cockpit.berichte') ? 'active' : '' }}">
        <span class="ti ti-file-analytics"></span><span>Berichte</span>
      </a>
    </nav>

    <div class="rail-foot">
      <a href="{{ route('cockpit.einstellungen') }}" style="padding:0;display:block">
        <div class="me" style="background:{{ request()->routeIs('cockpit.einstellungen') ? 'var(--accsoft)' : '' }}">
          <span class="ti ti-settings" style="font-size:18px;color:var(--dim)"></span>
          <div><div class="nm">Einstellungen</div></div>
        </div>
      </a>
      <div class="me">
        <div class="av">{{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 2)) }}</div>
        <div>
          <div class="nm">{{ Auth::user()->name ?? 'Benutzer' }}</div>
          <div class="rl">{{ Auth::user()->role ?? 'Administrator' }}</div>
        </div>
      </div>
    </div>
  </aside>

  {{-- ====== Main Stage ====== --}}
  <main class="stage">
    {{ $slot }}
  </main>

</div>

@livewireScripts
</body>
</html>
