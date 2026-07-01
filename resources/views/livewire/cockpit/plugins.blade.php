<div>
<div class="topbar">
  <div class="topbar-left">
    <span class="crumb">Überwachung</span>
    <span class="crumb-sep">/</span>
    <h1>Plugins</h1>
  </div>
  <div class="topbar-actions">
    <button class="btn acc" wire:click="openModal">
      <span class="ti ti-plus"></span>{{ $tab === 'own' ? 'Eigenes Plugin' : 'Externes Plugin' }}
    </button>
  </div>
</div>

<div class="scroll">
<div class="pad" style="display:flex;flex-direction:column;gap:16px">

  @if(session('plugin'))
  <div class="flash-ok"><span class="ti ti-circle-check"></span>{{ session('plugin') }}</div>
  @endif

  {{-- Tabs --}}
  <div class="chip-row">
    <span class="chip {{ $tab==='own' ? 'active' : '' }}" wire:click="setTab('own')">
      <span class="ti ti-code"></span>Eigene Plugins <strong style="margin-left:3px">{{ $own->count() }}</strong>
    </span>
    <span class="chip {{ $tab==='external' ? 'active' : '' }}" wire:click="setTab('external')">
      <span class="ti ti-puzzle"></span>Externe Plugins <strong style="margin-left:3px">{{ $external->count() }}</strong>
    </span>
  </div>

  @if($tab === 'own')
  {{-- Eigene Plugins --}}
  <div class="card">
    <div class="sec-h">
      <span class="ti ti-code"></span><h3>Eigene Plugins</h3>
      <span class="badge badge-acc" style="margin-left:auto"><span class="ti ti-rocket"></span>Update-System geplant</span>
    </div>
    <div style="padding:14px 18px;border-bottom:1px solid var(--line);font-size:12.5px;color:var(--dim)">
      Hier verwaltest du deine selbst entwickelten WordPress-Plugins. Sie bilden später die Basis für die
      zentrale Update-Auslieferung (Versionen, Changelog, Staged Rollout).
    </div>
    @forelse($own as $p)
    <div class="prow">
      <span class="ti ti-code prow-icon text-acc"></span>
      <div class="prow-body">
        <div class="prow-title">{{ $p->name }} @if($p->current_version)<span class="badge badge-off" style="margin-left:6px">v{{ $p->current_version }}</span>@endif</div>
        <div class="prow-meta">
          @if($p->slug)<span><code>{{ $p->slug }}</code></span><span class="sep">·</span>@endif
          @if($p->repo_url)<a href="{{ $p->repo_url }}" target="_blank" rel="noopener" class="lnk">Repo <span class="ti ti-external-link"></span></a>@else<span>kein Repo</span>@endif
        </div>
        @if($p->notes)<div style="font-size:12px;color:var(--dim);margin-top:5px">{{ $p->notes }}</div>@endif
      </div>
      <div class="prow-actions">
        <button class="icon-link" wire:click="deletePlugin({{ $p->id }})" wire:confirm="Plugin „{{ $p->name }}“ wirklich löschen?" title="Löschen"><span class="ti ti-trash"></span></button>
      </div>
    </div>
    @empty
    <div class="empty">
      <span class="ti ti-code"></span>
      <h3>Noch keine eigenen Plugins</h3>
      <p>Lege dein erstes eigenes Plugin an, um es später zentral mit Updates zu versorgen.</p>
    </div>
    @endforelse
  </div>

  @else
  {{-- Externe Plugins --}}
  <div class="card">
    <div class="sec-h"><span class="ti ti-puzzle"></span><h3>Externe Plugins</h3></div>
    <div style="padding:14px 18px;border-bottom:1px solid var(--line);font-size:12.5px;color:var(--dim)">
      Zugekaufte/fremde Plugins mit Zuordnung zum gebuchten Paket, eigenen Notizen/Erklärungen und (später)
      automatisch gesammelten News (z. B. „Version X fehlerhaft", „Konflikt mit Plugin Y").
    </div>
    @forelse($external as $p)
    @php $pkg = $p->package(); @endphp
    <div class="prow">
      <span class="ti ti-puzzle prow-icon text-acc"></span>
      <div class="prow-body">
        <div class="prow-title">
          {{ $p->name }}
          @if($pkg)<span class="pkg-chip booked" style="margin-left:6px;font-size:10.5px"><span class="ti {{ $pkg->iconClass() }}"></span>{{ $pkg->name }}</span>@endif
        </div>
        <div class="prow-meta">
          @if($p->homepage)<a href="{{ $p->homepage }}" target="_blank" rel="noopener" class="lnk">Website <span class="ti ti-external-link"></span></a><span class="sep">·</span>@endif
          <span class="badge badge-off"><span class="ti ti-news"></span>{{ count($p->news ?? []) }} News</span>
        </div>
        @if($p->notes)<div style="font-size:12px;color:var(--dim);margin-top:5px">{{ $p->notes }}</div>@endif
      </div>
      <div class="prow-actions">
        <button class="icon-link" wire:click="deletePlugin({{ $p->id }})" wire:confirm="Plugin „{{ $p->name }}“ wirklich löschen?" title="Löschen"><span class="ti ti-trash"></span></button>
      </div>
    </div>
    @empty
    <div class="empty">
      <span class="ti ti-puzzle"></span>
      <h3>Noch keine externen Plugins</h3>
      <p>Füge ein externes Plugin hinzu und ordne es einem Paket zu.</p>
    </div>
    @endforelse
  </div>
  @endif

</div>
</div>

{{-- Modal: Plugin anlegen --}}
@if($showModal)
<div class="overlay" wire:click.self="closeModal">
  <div class="modal">
    <div class="modal-head"><h2>{{ $tab === 'own' ? 'Eigenes Plugin anlegen' : 'Externes Plugin anlegen' }}</h2><button class="icon-link ml-auto" wire:click="closeModal"><span class="ti ti-x"></span></button></div>
    <div class="modal-body">
      <div>
        <label class="lbl">Name *</label>
        <input type="text" class="field @error('newName') error @enderror" wire:model="newName" placeholder="Plugin-Name">
        @error('newName')<div style="font-size:12px;color:var(--crit);margin-top:4px">{{ $message }}</div>@enderror
      </div>

      @if($tab === 'own')
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div><label class="lbl">Slug</label><input type="text" class="field" wire:model="newSlug" placeholder="mein-plugin"></div>
        <div><label class="lbl">Version</label><input type="text" class="field" wire:model="newVersion" placeholder="1.0.0"></div>
      </div>
      <div>
        <label class="lbl">Repo / Update-Quelle (URL)</label>
        <input type="url" class="field @error('newRepoUrl') error @enderror" wire:model="newRepoUrl" placeholder="https://github.com/…">
        @error('newRepoUrl')<div style="font-size:12px;color:var(--crit);margin-top:4px">{{ $message }}</div>@enderror
      </div>
      @else
      <div>
        <label class="lbl">Zugehöriges Paket</label>
        <select class="field" wire:model="newPackageKey">
          <option value="">– kein Paket –</option>
          @foreach($packages as $pkg)
            <option value="{{ $pkg->key }}">{{ $pkg->name }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="lbl">Website (URL)</label>
        <input type="url" class="field @error('newHomepage') error @enderror" wire:model="newHomepage" placeholder="https://…">
        @error('newHomepage')<div style="font-size:12px;color:var(--crit);margin-top:4px">{{ $message }}</div>@enderror
      </div>
      @endif

      <div>
        <label class="lbl">Notizen / Erklärungen</label>
        <textarea class="field" wire:model="newNotes" rows="3" placeholder="Wofür ist das Plugin, Besonderheiten, Hinweise…"></textarea>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn ghost" wire:click="closeModal">Abbrechen</button>
      <button class="btn acc" wire:click="createPlugin"><span class="ti ti-check"></span>Speichern</button>
    </div>
  </div>
</div>
@endif

</div>
