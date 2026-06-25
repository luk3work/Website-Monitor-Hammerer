<div>
<div class="topbar">
  <div class="topbar-left">
    <span class="crumb">System</span>
    <span class="crumb-sep">/</span>
    <h1>Benutzer</h1>
  </div>
  <div class="topbar-actions">
    <button class="btn acc"><span class="ti ti-user-plus"></span>Einladen</button>
  </div>
</div>
<div class="scroll">
<div class="pad" style="max-width:820px;display:flex;flex-direction:column;gap:16px">

  <div class="card">
    <div class="sec-h"><span class="ti ti-user-cog"></span><h3>Team-Mitglieder</h3><span class="cnt">{{ $users->count() }}</span></div>
    @forelse($users as $u)
    @php
      $col = ['#0EA5E9','#10B981','#A855F7','#F59E0B','#EF4444'][$u->id % 5];
      $role = $u->role ?? 'admin';
    @endphp
    <div class="user-card">
      <div class="user-av-lg" style="background:{{ $col }}">{{ strtoupper(substr($u->name,0,2)) }}</div>
      <div style="flex:1;min-width:0">
        <div class="un">
          {{ $u->name }}
          <span class="role-{{ $role === 'admin' ? 'admin' : ($role === 'editor' ? 'editor' : 'viewer') }}">
            {{ match($role) { 'admin'=>'Admin','editor'=>'Editor',default=>'Viewer' } }}
          </span>
        </div>
        <div class="ue">{{ $u->email }}</div>
      </div>
      <div style="display:flex;gap:6px">
        <button class="btn ghost sm"><span class="ti ti-pencil"></span>Bearbeiten</button>
      </div>
    </div>
    @empty
    <div class="empty"><span class="ti ti-users-off"></span><h3>Keine Benutzer</h3></div>
    @endforelse
  </div>

  <div class="card">
    <div class="sec-h"><span class="ti ti-shield-lock"></span><h3>Rollen & Berechtigungen</h3></div>
    <div style="padding:16px 18px">
      <table class="tbl">
        <thead>
          <tr><th>Rolle</th><th>Lesen</th><th>Sites verwalten</th><th>Einstellungen</th><th>Benutzer</th></tr>
        </thead>
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
