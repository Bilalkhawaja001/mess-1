@extends('layouts.app')
@section('title','Settings')
@section('page_title','Settings')

@section('content')
@php
  $activeTab = $tab ?? 'app';
  $messNameSuggestions = [
    'Executive Mess',
    'Centralized Mess',
    'Contractors Mess',
  ];
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
      <h6 class="mb-3">Create Department</h6>
      <form method="POST" action="{{ route('admin.accounting.departments.store') }}" class="row g-2 mb-3">
        @csrf
        <div class="col-md-3"><input name="code" class="form-control" placeholder="Dept Code (e.g. OPS)" required></div>
        <div class="col-md-6"><input name="name" class="form-control" placeholder="Department Name" required></div>
        <div class="col-md-3"><button class="btn btn-primary w-100">Save Department</button></div>
      </form>

      <div class="table-responsive">
        <table class="table table-sm table-bordered">
          <thead><tr><th>ID</th><th>Code</th><th>Name</th></tr></thead>
          <tbody>
            @forelse($departments as $d)
              <tr><td>{{ $d->id }}</td><td>{{ $d->code }}</td><td>{{ $d->name }}</td></tr>
            @empty
              <tr><td colspan="3" class="text-center text-muted">No departments yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="tab-pane fade {{ $activeTab==='messes' ? 'show active' : '' }}" id="tab-messes">
      <h6 class="mb-3">Create Mess Name</h6>
      <form method="POST" action="{{ route('admin.accounting.messes.store') }}" class="row g-2 mb-3">
        @csrf
        <div class="col-md-3"><input name="code" class="form-control" placeholder="Mess Code (e.g. EXECUTIVE)" required></div>
        <div class="col-md-4"><input name="name" class="form-control" placeholder="Mess Name" required></div>
        <div class="col-md-3">
          <select name="department_id" class="form-select">
            <option value="">No Department</option>
            @foreach($departments as $d)
              <option value="{{ $d->id }}">{{ $d->code }} - {{ $d->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-md-2"><button class="btn btn-primary w-100">Save Mess</button></div>
      </form>

      <div class="small text-muted mb-2">
        Suggested names from Flask flow: {{ implode(', ', $messNameSuggestions) }}
      </div>

      <div class="table-responsive">
        <table class="table table-sm table-bordered">
          <thead><tr><th>ID</th><th>Code</th><th>Name</th><th>Department</th></tr></thead>
          <tbody>
            @forelse($messes as $m)
              <tr>
                <td>{{ $m->id }}</td>
                <td>{{ $m->code }}</td>
                <td>{{ $m->name }}</td>
                <td>{{ $m->department?->name }}</td>
              </tr>
            @empty
              <tr><td colspan="4" class="text-center text-muted">No mess names yet.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>

    <div class="tab-pane fade {{ $activeTab==='rates' ? 'show active' : '' }}" id="tab-rates">
      <h6 class="mb-3">Create Rate Policy</h6>
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
        <table class="table table-sm table-bordered">
          <thead><tr><th>ID</th><th>Type</th><th>Value</th><th>From</th><th>To</th><th>Approved</th><th>Status</th></tr></thead>
          <tbody>
            @forelse($rates as $r)
              <tr>
                <td>{{ $r->id }}</td>
                <td>{{ $r->rate_type }}</td>
                <td>{{ $r->value }}</td>
                <td>{{ optional($r->effective_from)->format('Y-m-d') }}</td>
                <td>{{ optional($r->effective_to)->format('Y-m-d') }}</td>
                <td>{{ $r->approved_at ? 'YES' : 'NO' }}</td>
                <td>{{ $r->is_active ? 'Active' : 'Locked' }}</td>
              </tr>
            @empty
              <tr><td colspan="7" class="text-center text-muted">No rates yet.</td></tr>
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
