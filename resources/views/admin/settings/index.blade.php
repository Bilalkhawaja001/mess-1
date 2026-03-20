@extends('layouts.app')
@section('title','Settings')
@section('page_title','Settings')

@section('content')
@php
  $activeTab = $tab ?? 'app';
@endphp

<div class="card shadow-sm">
  <div class="card-header">
    <ul class="nav nav-tabs card-header-tabs" id="settingsTabs" role="tablist">
      <li class="nav-item"><button class="nav-link {{ $activeTab==='app' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-app" type="button">App Settings</button></li>
      <li class="nav-item"><button class="nav-link {{ $activeTab==='departments' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-departments" type="button">Departments</button></li>
      <li class="nav-item"><button class="nav-link {{ $activeTab==='messes' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-messes" type="button">Mess Names</button></li>
      <li class="nav-item"><button class="nav-link {{ $activeTab==='rates' ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tab-rates" type="button">Rates</button></li>
    </ul>
  </div>

  <div class="card-body tab-content">
    <div class="tab-pane fade {{ $activeTab==='app' ? 'show active' : '' }}" id="tab-app">
      <h6 class="mb-3">Save Setting</h6>
      <form method="POST" action="{{ route('admin.settings.store') }}" class="row g-2 mb-3">
        @csrf
        <div class="col-md-3"><input name="setting_key" class="form-control" placeholder="setting_key" required></div>
        <div class="col-md-4"><input name="setting_value" class="form-control" placeholder="value"></div>
        <div class="col-md-2">
          <select name="value_type" class="form-select">
            <option>string</option><option>int</option><option>float</option><option>bool</option><option>json</option>
          </select>
        </div>
        <div class="col-md-2 form-check mt-2">
          <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
          <label class="form-check-label">Active</label>
        </div>
        <div class="col-md-1"><button class="btn btn-primary">Save</button></div>
      </form>

      <div class="table-responsive">
        <table class="table table-sm">
          <thead><tr><th>Key</th><th>Value</th><th>Type</th><th>Active</th><th>Action</th></tr></thead>
          <tbody>
            @foreach($rows as $r)
              <tr>
                <td>{{ $r->setting_key }}</td>
                <td>{{ $r->setting_value }}</td>
                <td>{{ $r->value_type }}</td>
                <td>{{ $r->is_active ? 'Yes' : 'No' }}</td>
                <td>
                  <form method="POST" action="{{ route('admin.settings.toggle',$r->id) }}">
                    @csrf
                    <button class="btn btn-sm btn-outline-warning">Toggle</button>
                  </form>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>

    <div class="tab-pane fade {{ $activeTab==='departments' ? 'show active' : '' }}" id="tab-departments">
      <div class="d-flex justify-content-between align-items-end mb-3 gap-2 flex-wrap">
        <h6 class="mb-0">Departments</h6>
        <form method="GET" action="{{ route('admin.settings.index') }}" class="d-flex gap-2">
          <input type="hidden" name="tab" value="departments">
          <select name="department_status" class="form-select form-select-sm">
            <option value="all" {{ $departmentStatus==='all' ? 'selected' : '' }}>All</option>
            <option value="active" {{ $departmentStatus==='active' ? 'selected' : '' }}>Active</option>
            <option value="inactive" {{ $departmentStatus==='inactive' ? 'selected' : '' }}>Inactive</option>
          </select>
          <button class="btn btn-sm btn-outline-secondary">Filter</button>
        </form>
      </div>

      <form method="POST" action="{{ route('admin.settings.departments.store') }}" class="row g-2 mb-3">
        @csrf
        <div class="col-md-3"><input name="code" class="form-control" placeholder="Dept Code" required></div>
        <div class="col-md-7"><input name="name" class="form-control" placeholder="Department Name" required></div>
        <div class="col-md-2"><button class="btn btn-primary w-100">Create</button></div>
      </form>

      <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle">
          <thead><tr><th>ID</th><th>Code</th><th>Name</th><th>Status</th><th style="width:320px">Actions</th></tr></thead>
          <tbody>
            @forelse($departments as $d)
              <tr>
                <td>{{ $d->id }}</td>
                <td>{{ $d->code }}</td>
                <td>
                  <form method="POST" action="{{ route('admin.settings.departments.update', $d->id) }}" class="d-flex gap-2 align-items-center">
                    @csrf
                    <input type="text" name="name" value="{{ $d->name }}" class="form-control form-control-sm" required>
                    <div class="form-check mb-0">
                      <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ $d->is_active ? 'checked' : '' }}>
                    </div>
                    <button class="btn btn-sm btn-outline-primary">Save</button>
                  </form>
                </td>
                <td>{{ $d->is_active ? 'Active' : 'Inactive' }}</td>
                <td class="d-flex gap-2">
                  @if($d->is_active)
                    <form method="POST" action="{{ route('admin.settings.departments.remove', $d->id) }}">
                      @csrf
                      <button class="btn btn-sm btn-outline-danger">Remove</button>
                    </form>
                  @else
                    <form method="POST" action="{{ route('admin.settings.departments.reactivate', $d->id) }}">
                      @csrf
                      <button class="btn btn-sm btn-outline-success">Reactivate</button>
                    </form>
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="5" class="text-center text-muted">No departments found.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="tab-pane fade {{ $activeTab==='messes' ? 'show active' : '' }}" id="tab-messes">
      <div class="d-flex justify-content-between align-items-end mb-3 gap-2 flex-wrap">
        <h6 class="mb-0">Mess Names</h6>
        <form method="GET" action="{{ route('admin.settings.index') }}" class="d-flex gap-2">
          <input type="hidden" name="tab" value="messes">
          <select name="messes_status" class="form-select form-select-sm">
            <option value="all" {{ $messesStatus==='all' ? 'selected' : '' }}>All</option>
            <option value="active" {{ $messesStatus==='active' ? 'selected' : '' }}>Active</option>
            <option value="removed" {{ $messesStatus==='removed' ? 'selected' : '' }}>Removed</option>
          </select>
          <button class="btn btn-sm btn-outline-secondary">Filter</button>
        </form>
      </div>

      <form method="POST" action="{{ route('admin.settings.messes.store') }}" class="row g-2 mb-3">
        @csrf
        <div class="col-md-3"><input name="code" class="form-control" placeholder="Mess Code" required></div>
        <div class="col-md-4"><input name="name" class="form-control" placeholder="Mess Name" required></div>
        <div class="col-md-3">
          <select name="department_id" class="form-select">
            <option value="">No Department</option>
            @foreach($departmentOptions as $d)
              <option value="{{ $d->id }}">{{ $d->code }} - {{ $d->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2"><button class="btn btn-primary w-100">Create</button></div>
      </form>

      <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle">
          <thead><tr><th>ID</th><th>Code</th><th>Name</th><th>Department</th><th>Status</th><th style="width:360px">Actions</th></tr></thead>
          <tbody>
            @forelse($messes as $m)
              <tr>
                <td>{{ $m->id }}</td>
                <td>{{ $m->code }}</td>
                <td colspan="2">
                  <form method="POST" action="{{ route('admin.settings.messes.update', $m->id) }}" class="row g-2 align-items-center">
                    @csrf
                    <div class="col-md-4"><input type="text" name="name" value="{{ $m->name }}" class="form-control form-control-sm" required></div>
                    <div class="col-md-5">
                      <select name="department_id" class="form-select form-select-sm">
                        <option value="">No Department</option>
                        @foreach($departmentOptions as $d)
                          <option value="{{ $d->id }}" {{ $m->department_id === $d->id ? 'selected' : '' }}>{{ $d->code }} - {{ $d->name }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="col-md-1 form-check mb-0">
                      <input class="form-check-input" type="checkbox" name="is_active" value="1" {{ $m->is_active ? 'checked' : '' }}>
                    </div>
                    <div class="col-md-2"><button class="btn btn-sm btn-outline-primary w-100">Save</button></div>
                  </form>
                </td>
                <td>{{ $m->is_active ? 'Active' : 'Removed' }}</td>
                <td class="d-flex gap-2">
                  @if($m->is_active)
                    <form method="POST" action="{{ route('admin.settings.messes.remove', $m->id) }}">
                      @csrf
                      <button class="btn btn-sm btn-outline-danger">Remove</button>
                    </form>
                  @else
                    <form method="POST" action="{{ route('admin.settings.messes.reactivate', $m->id) }}">
                      @csrf
                      <button class="btn btn-sm btn-outline-success">Reactivate</button>
                    </form>
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="6" class="text-center text-muted">No mess names found.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="tab-pane fade {{ $activeTab==='rates' ? 'show active' : '' }}" id="tab-rates">
      <h6 class="mb-3">Add Rate Policy</h6>
      <form method="POST" action="{{ route('admin.rates.store') }}" class="row g-2 mb-3">
        @csrf
        <input type="hidden" name="return_to" value="settings">
        <div class="col-md-3">
          <select name="rate_type" class="form-select" required>
            @foreach($rateTypes as $type => $label)
              <option value="{{ $type }}">{{ $label }} ({{ $type }})</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2"><input type="number" step="0.01" min="0" name="value" class="form-control" placeholder="Value" required></div>
        <div class="col-md-2"><input type="date" name="effective_from" class="form-control" required></div>
        <div class="col-md-2"><input type="date" name="effective_to" class="form-control"></div>
        <div class="col-md-2 form-check mt-2">
          <input class="form-check-input" type="checkbox" name="is_active" value="1" checked>
          <label class="form-check-label">Active</label>
        </div>
        <div class="col-md-1"><button class="btn btn-primary w-100">Add</button></div>
      </form>

      <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle">
          <thead><tr><th>ID</th><th>Type</th><th>Value</th><th>From</th><th>To</th><th>Approval</th><th>Lock</th><th>Actions</th></tr></thead>
          <tbody>
            @forelse($rates as $r)
              <tr>
                <td>{{ $r->id }}</td>
                <td>{{ $rateTypes[$r->rate_type] ?? $r->rate_type }}</td>
                <td>{{ $r->value }}</td>
                <td>{{ optional($r->effective_from)->format('Y-m-d') }}</td>
                <td>{{ optional($r->effective_to)->format('Y-m-d') }}</td>
                <td>{{ $r->approved_at ? 'Approved' : 'Unapproved' }}</td>
                <td>{{ $r->is_active ? 'Unlocked' : 'Locked' }}</td>
                <td class="d-flex gap-2 flex-wrap">
                  <form method="POST" action="{{ route('admin.rates.toggle-approve', $r->id) }}">@csrf
                    <button class="btn btn-sm btn-outline-success">{{ $r->approved_at ? 'Unapprove' : 'Approve' }}</button>
                  </form>
                  <form method="POST" action="{{ route('admin.rates.toggle-lock', $r->id) }}">@csrf
                    <button class="btn btn-sm btn-outline-warning">{{ $r->is_active ? 'Lock' : 'Unlock' }}</button>
                  </form>
                  @if(!$r->approved_at)
                  <form method="POST" action="{{ route('admin.rates.delete.legacy', $r->id) }}">@csrf
                    <button class="btn btn-sm btn-outline-danger">Delete</button>
                  </form>
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="8" class="text-center text-muted">No rates yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
  (() => {
    const tabMap = { app:'tab-app', departments:'tab-departments', messes:'tab-messes', rates:'tab-rates' };
    document.querySelectorAll('#settingsTabs button[data-bs-toggle="tab"]').forEach(btn => {
      btn.addEventListener('shown.bs.tab', e => {
        const pane = (e.target.getAttribute('data-bs-target') || '').replace('#tab-','');
        const u = new URL(window.location.href);
        u.searchParams.set('tab', pane);
        history.replaceState({}, '', u.toString());
      });
    });

    const current = new URL(window.location.href).searchParams.get('tab');
    const id = tabMap[current];
    if (id) {
      const btn = document.querySelector(`[data-bs-target="#tab-${current}"]`);
      if (btn && window.bootstrap?.Tab) {
        bootstrap.Tab.getOrCreateInstance(btn).show();
      }
    }
  })();
</script>
@endsection
