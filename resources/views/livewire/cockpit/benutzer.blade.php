<div>
<div class="topbar">
  <div class="topbar-title">
    <div class="crumb">Verwaltung</div>
    <h1>Benutzer</h1>
  </div>
  <button class="btn acc"><span class="ti ti-user-plus"></span>Benutzer einladen</button>
</div>

<div class="scroll">
<div class="pad" style="max-width:820px">

  <div class="card">
    <div class="sec-h">
      <span class="ti ti-user-cog"></span>
      <h3>Team-Mitglieder</h3>
      <span class="cnt">{{ $users->count() }}</span>
    </div>
    @forelse($users as $u)
    @php
      $col = ['#d7263d','#0ea5e9','#10b981','#a855f7','#f59e0b'][$u->id % 5];
      $role = $u->role ?? 'admin';
    @endphp
    <div class="user-card">
      <div class="user-av" style="background:{{ $col }}">{{ strtoupper(substr($u->name,0,2)) }}</div>
      <div style="flex:1">
        <div class="un">{{ $u->name }}
          <span class="ur {{ $role === 'admin' ? 'role-admin' : ($role === 'editor' ? 'role-editor' : 'role-viewer') }}">
            {{ match($role) { 'admin' => 'Admin', 'editor' => 'Editor', default => 'Viewer' } }}
          </span>
        </div>
        <div class="ue">{{ $u->email }}</div>
      </div>
      <div style="display:flex;gap:8px">
        <button class="btn ghost" style="padding:6px 10px;font-size:11.5px"><span class="ti ti-pencil"></span>Bearbeiten</button>
        <button class="btn ghost" style="padding:6px 10px;font-size:11.5px;color:var(--crit)"><span class="ti ti-trash"></span></button>
      </div>
    </div>
    @empty
    <div class="stub"><span class="ti ti-users-off"></span><h3>Keine Benutzer</h3><p>Noch keine Team-Mitglieder angelegt.</p></div>
    @endforelse
  </div>

  <div class="card" style="margin-top:18px">
    <div class="sec-h"><span class="ti ti-shield-lock"></span><h3>Rollen &amp; Berechtigungen</h3></div>
    <div style="padding:18px">
      <table class="tbl">
        <thead><tr><th>Rolle</th><th>Cockpit lesen</th><th>Sites verwalten</th><th>Einstellungen</th><th>Benutzer verwalten</th></tr></thead>
        <tbody>
          <tr><td style="font-weight:600">Admin</td><td><span class="dot d-ok"></span></td><td><span class="dot d-ok"></span></td><td><span class="dot d-ok"></span></td><td><span class="dot d-ok"></span></td></tr>
          <tr><td style="font-weight:600">Editor</td><td><span class="dot d-ok"></span></td><td><span class="dot d-ok"></span></td><td><span class="dot d-off"></span></td><td><span class="dot d-off"></span></td></tr>
          <tr><td style="font-weight:600">Viewer</td><td><span class="dot d-ok"></span></td><td><span class="dot d-off"></span></td><td><span class="dot d-off"></span></td><td><span class="dot d-off"></span></td></tr>
        </tbody>
      </table>
    </div>
  </div>

</div>
</div>
</div>
