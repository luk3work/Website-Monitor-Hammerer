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

  @php $me = auth()->user(); $mcol = ['#B9A564','#10B981','#A855F7','#F59E0B','#EF4444'][$me->id % 5]; @endphp

  {{-- Eigenes Profil inkl. Bild-Upload --}}
  <div class="card">
    <div class="sec-h"><span class="ti ti-id-badge-2"></span><h3>Mein Profil</h3></div>
    @if(session('profile'))
      <div style="margin:14px 18px 0"><div class="flash-ok"><span class="ti ti-circle-check"></span>{{ session('profile') }}</div></div>
    @endif
    <div style="padding:18px;display:flex;align-items:center;gap:18px">
      @if($me->avatarUrl())
        <img src="{{ $me->avatarUrl() }}" class="avatar-xl" alt="Profilbild">
      @else
        <div class="avatar-xl" style="background:{{ $mcol }};display:grid;place-items:center;font-weight:800;font-size:22px;color:#fff">{{ strtoupper(substr($me->name,0,2)) }}</div>
      @endif
      <div style="flex:1;min-width:0">
        <div style="font-weight:700;font-size:15px">{{ $me->name }}</div>
        <div class="ue">{{ $me->email }}</div>
        <div style="display:flex;gap:8px;margin-top:12px;align-items:center;flex-wrap:wrap">
          <label class="btn acc sm" style="cursor:pointer;margin:0">
            <span class="ti ti-upload"></span>Bild wählen
            <input type="file" wire:model="avatar" accept="image/*" style="display:none">
          </label>
          @if($me->avatarUrl())
            <button class="btn ghost sm" wire:click="removeAvatar"><span class="ti ti-trash"></span>Entfernen</button>
          @endif
          <span wire:loading wire:target="avatar" style="font-size:12px;color:var(--dim)">Lädt…</span>
        </div>
        @error('avatar') <div style="font-size:12px;color:var(--crit);margin-top:6px">{{ $message }}</div> @enderror
        <div style="font-size:11px;color:var(--faint);margin-top:6px">JPG oder PNG, max. 4 MB. Quadratisch wirkt am besten.</div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="sec-h"><span class="ti ti-user-cog"></span><h3>Team-Mitglieder</h3><span class="cnt">{{ $users->count() }}</span></div>
    @forelse($users as $u)
    @php
      $col = ['#B9A564','#10B981','#A855F7','#F59E0B','#EF4444'][$u->id % 5];
      $role = $u->role ?? 'admin';
    @endphp
    <div class="user-card">
      @if($u->avatarUrl())
        <img src="{{ $u->avatarUrl() }}" class="user-av-lg" alt="">
      @else
        <div class="user-av-lg" style="background:{{ $col }}">{{ strtoupper(substr($u->name,0,2)) }}</div>
      @endif
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
