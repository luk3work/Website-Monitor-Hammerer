<div>
{{-- Topbar --}}
<div class="topbar">
  <div class="topbar-left">
    <span class="crumb">Ops Cockpit</span>
    <span class="crumb-sep">/</span>
    <h1>Aufgaben</h1>
  </div>
  <div class="topbar-actions">
    <div class="topbar-search">
      <span class="ti ti-search"></span>
      <input type="text" wire:model.live.debounce.300ms="search" placeholder="Aufgabe suchen…" aria-label="Aufgaben durchsuchen">
    </div>
    <button class="btn acc" wire:click="openModal"><span class="ti ti-plus"></span>Neue Aufgabe</button>
  </div>
</div>

<div class="scroll">
<div class="pad" style="display:flex;flex-direction:column;gap:16px">

  @if(session('created'))
  <div class="flash-ok"><span class="ti ti-circle-check"></span>{{ session('created') }}</div>
  @endif

  {{-- KPI Row --}}
  <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px">
    <div class="kpi-card k-warn" style="padding:14px 18px">
      <div class="kpi-top"><span class="kpi-label">Offen</span><span class="ti ti-circle-dot kpi-icon"></span></div>
      <div class="kpi-value" style="font-size:24px">{{ $openCount }}</div>
    </div>
    <div class="kpi-card k-crit" style="padding:14px 18px">
      <div class="kpi-top"><span class="kpi-label">Kritisch</span><span class="ti ti-alert-circle kpi-icon"></span></div>
      <div class="kpi-value" style="font-size:24px;{{ $critCount>0?'color:var(--crit)':'' }}">{{ $critCount }}</div>
    </div>
    <div class="kpi-card k-ok" style="padding:14px 18px">
      <div class="kpi-top"><span class="kpi-label">Heute erledigt</span><span class="ti ti-circle-check kpi-icon"></span></div>
      <div class="kpi-value" style="font-size:24px;color:var(--ok)">{{ $doneToday }}</div>
    </div>
  </div>

  {{-- Filters --}}
  <div class="chip-row">
    <span class="chip {{ !$filterStatus ? 'active' : '' }}" wire:click="$set('filterStatus','')">Alle</span>
    <span class="chip {{ $filterStatus==='open' ? 'active-warn' : '' }}" wire:click="$set('filterStatus','open')">
      <span class="dot d-warn"></span>Offen
    </span>
    <span class="chip {{ $filterStatus==='done' ? 'active-ok' : '' }}" wire:click="$set('filterStatus','done')">
      <span class="dot d-ok"></span>Erledigt
    </span>
    <span style="flex:1"></span>
    <span class="chip {{ $filterSev==='critical' ? 'active-crit' : '' }}" wire:click="$set('filterSev', $filterSev==='critical' ? '' : 'critical')">
      <span class="dot d-crit"></span>Kritisch
    </span>
    <span class="chip {{ $filterSev==='warning' ? 'active-warn' : '' }}" wire:click="$set('filterSev', $filterSev==='warning' ? '' : 'warning')">
      <span class="dot d-warn"></span>Wichtig
    </span>
  </div>

  {{-- Task Table --}}
  <div class="card">
    @if($tasks->count() > 0)
    <table class="tbl">
      <thead>
        <tr>
          <th>Aufgabe</th>
          <th>Website</th>
          <th>Priorität</th>
          <th>Typ</th>
          <th>Frist</th>
          <th>Zuständig</th>
          <th>Status</th>
          <th></th>
        </tr>
      </thead>
      <tbody>
        @foreach($tasks as $task)
        @php
          $sevClass = match($task->severity?->value ?? $task->severity) { 'critical'=>'crit','warning'=>'warn',default=>'info' };
        @endphp
        <tr>
          <td>
            <div style="font-weight:600;font-size:13px">{{ $task->title }}</div>
            @if($task->description)
            <div style="font-size:11.5px;color:var(--dim);margin-top:2px">{{ Str::limit($task->description, 60) }}</div>
            @endif
          </td>
          <td>
            @if($task->site)
              <div style="font-size:13px">{{ $task->site->name }}</div>
              <div style="font-size:11.5px;color:var(--faint)">{{ $task->site->customer?->name }}</div>
            @else
              <span style="color:var(--faint)">–</span>
            @endif
          </td>
          <td>
            <span class="badge badge-{{ $sevClass }}">
              <span class="dot d-{{ $sevClass }}"></span>
              {{ match($task->severity?->value ?? $task->severity) { 'critical'=>'Kritisch','warning'=>'Wichtig',default=>'Info' } }}
            </span>
          </td>
          <td><span style="font-size:12px;color:var(--dim)">{{ $task->type }}</span></td>
          <td>
            @if($task->due_date)
              @php $overdue = $task->due_date->isPast() && !in_array($task->status?->value ?? $task->status, ['done','dismissed']); @endphp
              <span style="font-size:12.5px;{{ $overdue ? 'color:var(--crit);font-weight:700' : 'color:var(--dim)' }}">
                {{ $task->due_date->format('d.m.Y') }}{{ $overdue ? ' !' : '' }}
              </span>
            @else
              <span style="color:var(--faint)">–</span>
            @endif
          </td>
          <td>
            @if($task->assignee)
              @php $col = ['#0EA5E9','#10B981','#A855F7','#F59E0B','#EF4444'][$task->assignee->id % 5]; @endphp
              <div style="display:flex;align-items:center;gap:7px">
                <div class="user-av" style="background:{{ $col }};width:24px;height:24px;font-size:10px">{{ strtoupper(substr($task->assignee->name,0,2)) }}</div>
                <span style="font-size:12.5px">{{ explode(' ', $task->assignee->name)[0] }}</span>
              </div>
            @else
              <span style="color:var(--faint);font-size:12px">Nicht zugewiesen</span>
            @endif
          </td>
          <td>
            @php $st = $task->status?->value ?? $task->status; @endphp
            @php $stClass = match($st) { 'open'=>'warn','in_progress'=>'acc','blocked'=>'crit','done'=>'ok','dismissed'=>'off',default=>'off' }; @endphp
            <span class="badge badge-{{ $stClass }}">{{ $task->status?->label() ?? $st }}</span>
          </td>
          <td>
            <div style="display:flex;gap:6px">
              @if(in_array($task->status?->value ?? $task->status, ['open','in_progress','blocked']))
                <button class="btn ghost sm" wire:click="updateStatus({{ $task->id }}, 'done')" title="Erledigen">
                  <span class="ti ti-check"></span>
                </button>
                <button class="btn ghost sm" wire:click="updateStatus({{ $task->id }}, 'in_progress')" title="In Arbeit">
                  <span class="ti ti-player-play"></span>
                </button>
              @else
                <button class="btn ghost sm" wire:click="updateStatus({{ $task->id }}, 'open')" title="Wieder öffnen">
                  <span class="ti ti-refresh"></span>
                </button>
              @endif
            </div>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
    <div style="padding:12px 16px;border-top:1px solid var(--line)">{{ $tasks->links() }}</div>
    @else
    <div class="empty">
      <span class="ti ti-checklist"></span>
      <h3>Keine Aufgaben gefunden</h3>
      <p>Kein Handlungsbedarf mit diesen Filtern — oder noch keine Aufgaben angelegt.</p>
      <button class="btn acc" wire:click="openModal"><span class="ti ti-plus"></span>Erste Aufgabe anlegen</button>
    </div>
    @endif
  </div>

</div>
</div>

{{-- Create Task Modal --}}
@if($showModal)
<div class="overlay" wire:click.self="closeModal" role="dialog" aria-modal="true" aria-labelledby="modal-title">
  <div class="modal">
    <div class="modal-head">
      <h2 id="modal-title">Neue Aufgabe</h2>
      <button class="btn ghost sm icon-btn" wire:click="closeModal" aria-label="Schließen"><span class="ti ti-x"></span></button>
    </div>
    <div class="modal-body">
      <div>
        <label class="lbl" for="new-title">Titel *</label>
        <input id="new-title" class="field" type="text" wire:model="newTitle" placeholder="Was muss erledigt werden?" autofocus>
        @error('newTitle') <div style="font-size:12px;color:var(--crit);margin-top:4px">{{ $message }}</div> @enderror
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div>
          <label class="lbl">Priorität *</label>
          <select class="field" wire:model="newSeverity">
            <option value="info">Info</option>
            <option value="warning">Wichtig</option>
            <option value="critical">Kritisch</option>
          </select>
        </div>
        <div>
          <label class="lbl">Typ</label>
          <select class="field" wire:model="newType">
            <option value="manual">Manuell</option>
            <option value="update">Update</option>
            <option value="ssl_expiry">SSL-Ablauf</option>
            <option value="domain_expiry">Domain-Ablauf</option>
            <option value="compliance">Compliance</option>
            <option value="security">Sicherheit</option>
          </select>
        </div>
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div>
          <label class="lbl">Website</label>
          <select class="field" wire:model="newSiteId">
            <option value="0">– Keine –</option>
            @foreach($sites as $site)
              <option value="{{ $site->id }}">{{ $site->customer?->name ? $site->customer->name.' – ' : '' }}{{ $site->name }}</option>
            @endforeach
          </select>
        </div>
        <div>
          <label class="lbl">Frist</label>
          <input class="field" type="date" wire:model="newDue">
        </div>
      </div>
      <div>
        <label class="lbl">Zuständig</label>
        <select class="field" wire:model="newAssignee">
          <option value="">– Nicht zugewiesen –</option>
          @foreach($users as $user)
            <option value="{{ $user->id }}">{{ $user->name }}</option>
          @endforeach
        </select>
      </div>
      <div>
        <label class="lbl">Beschreibung</label>
        <textarea class="field" wire:model="newDesc" rows="3" placeholder="Optionale Details…" style="resize:vertical"></textarea>
      </div>
    </div>
    <div class="modal-foot">
      <button class="btn ghost" wire:click="closeModal">Abbrechen</button>
      <button class="btn acc" wire:click="createTask"><span class="ti ti-check"></span>Aufgabe erstellen</button>
    </div>
  </div>
</div>
@endif
</div>
