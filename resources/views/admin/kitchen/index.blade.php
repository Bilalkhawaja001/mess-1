@extends('layouts.app')

@section('title','Meal Planning / Kitchen')
@section('page_title','Meal Planning / Kitchen')

@section('content')
<div class="row g-3">
    <div class="col-lg-6">
        <div class="card shadow-sm mb-3"><div class="card-header">Create Menu</div><div class="card-body">
            <form method="POST" action="{{ route('admin.kitchen.menus.store') }}" class="row g-2">@csrf
                <div class="col-md-6"><input class="form-control" name="name" placeholder="Menu name" required></div>
                <div class="col-md-4"><input class="form-control" name="meal_type" placeholder="Meal type" required></div>
                <div class="col-md-2"><button class="btn btn-primary w-100">Add</button></div>
            </form>
        </div></div>

        <div class="card shadow-sm"><div class="card-header">Menus</div><div class="card-body table-responsive">
            <table class="table table-sm"><thead><tr><th>ID</th><th>Name</th><th>Type</th><th>Actions</th></tr></thead><tbody>
                @foreach($menus as $m)
                    <tr>
                        <td>{{ $m->id }}</td><td>{{ $m->name }}</td><td>{{ $m->meal_type }}</td>
                        <td class="d-flex gap-1">
                            <form method="POST" action="{{ route('admin.kitchen.menus.edit.legacy',$m) }}" class="d-flex gap-1">@csrf
                                <input type="hidden" name="name" value="{{ $m->name }}">
                                <input type="hidden" name="meal_type" value="{{ $m->meal_type }}">
                                <button class="btn btn-sm btn-outline-secondary">Save</button>
                            </form>
                            <form method="POST" action="{{ route('admin.kitchen.menus.delete.legacy',$m) }}">@csrf<button class="btn btn-sm btn-outline-danger">Delete</button></form>
                        </td>
                    </tr>
                @endforeach
            </tbody></table>
        </div></div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm mb-3"><div class="card-header">Add Recipe Line</div><div class="card-body">
            <form method="POST" action="{{ route('admin.kitchen.recipes.store') }}" class="row g-2">@csrf
                <div class="col-md-4"><select name="menu_id" class="form-select" required>@foreach($menus as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach</select></div>
                <div class="col-md-4"><select name="item_id" class="form-select" required>@foreach($items as $i)<option value="{{ $i->id }}">{{ $i->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><input class="form-control" name="qty_per_serving" step="0.0001" type="number" required></div>
                <div class="col-md-2"><button class="btn btn-primary w-100">Add</button></div>
            </form>
        </div></div>

        <div class="card shadow-sm"><div class="card-header">Recipes</div><div class="card-body table-responsive">
            <table class="table table-sm"><thead><tr><th>Menu</th><th>Item</th><th>Qty/Serving</th><th></th></tr></thead><tbody>
                @foreach($recipes as $r)
                    <tr>
                        <td>{{ $menus->firstWhere('id',$r->menu_id)?->name ?? $r->menu_id }}</td>
                        <td>{{ $items->firstWhere('id',$r->item_id)?->name ?? $r->item_id }}</td>
                        <td>{{ $r->qty_per_serving }}</td>
                        <td><form method="POST" action="{{ route('admin.kitchen.recipes.delete.legacy',$r) }}">@csrf<button class="btn btn-sm btn-outline-danger">Delete</button></form></td>
                    </tr>
                @endforeach
            </tbody></table>
        </div></div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm mb-3"><div class="card-header">Create Meal Plan</div><div class="card-body">
            <form method="POST" action="{{ route('admin.kitchen.plans.store') }}" class="row g-2">@csrf
                <div class="col-md-4"><input name="plan_date" type="date" class="form-control" required></div>
                <div class="col-md-4"><select name="menu_id" class="form-select" required>@foreach($menus as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><input name="planned_servings" type="number" min="1" class="form-control" required></div>
                <div class="col-md-2"><button class="btn btn-primary w-100">Add</button></div>
            </form>
        </div></div>
        <div class="card shadow-sm"><div class="card-header">Meal Plans</div><div class="card-body table-responsive"><table class="table table-sm"><thead><tr><th>Date</th><th>Menu</th><th>Servings</th><th></th></tr></thead><tbody>
            @foreach($plans as $p)<tr><td>{{ $p->plan_date }}</td><td>{{ $menus->firstWhere('id',$p->menu_id)?->name ?? $p->menu_id }}</td><td>{{ $p->planned_servings }}</td><td class="d-flex gap-1"><form method="POST" action="{{ route('admin.kitchen.plans.approve.legacy',$p) }}">@csrf<button class="btn btn-sm btn-outline-success">Approve</button></form><form method="POST" action="{{ route('admin.kitchen.plans.edit.legacy',$p) }}">@csrf<input type="hidden" name="plan_date" value="{{ $p->plan_date }}"><input type="hidden" name="menu_id" value="{{ $p->menu_id }}"><input type="hidden" name="planned_servings" value="{{ $p->planned_servings }}"><button class="btn btn-sm btn-outline-secondary">Save</button></form></td></tr>@endforeach
        </tbody></table></div></div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm mb-3"><div class="card-header">Post Kitchen Issue</div><div class="card-body">
            <form method="POST" action="{{ route('admin.kitchen.issues.store') }}" class="row g-2">@csrf
                <div class="col-md-3"><input name="issue_date" type="date" class="form-control" required></div>
                <div class="col-md-3"><select name="item_id" id="kitchen-item-select" class="form-select" required>@foreach($items as $i)<option value="{{ $i->id }}">{{ $i->name }}</option>@endforeach</select></div>
                <div class="col-md-2"><input name="quantity" id="kitchen-qty-input" type="number" step="0.001" min="0.001" class="form-control" required></div>
                <div class="col-md-2">
                    <select name="issue_type" class="form-select" required>
                        <option value="CONSUMPTION">Consumption</option>
                        <option value="WASTAGE">Wastage</option>
                        <option value="DAMAGE">Damage</option>
                        <option value="EXPIRED">Expired</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="mess_id" class="form-select">
                        <option value="">Mess (optional)</option>
                        @foreach($messes as $mess)
                            <option value="{{ $mess->id }}">{{ $mess->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <select name="unit_code" id="kitchen-unit-select" class="form-select">
                        <option value="">Base unit</option>
                    </select>
                    <div class="small text-muted" id="kitchen-conversion-preview"></div>
                </div>
                <div class="col-md-3"><input name="remarks" class="form-control" placeholder="remarks"></div>
                <div class="col-12"><button class="btn btn-primary">Post Issue</button></div>
            </form>
        </div></div>
        <div class="card shadow-sm"><div class="card-header">Kitchen Issues</div><div class="card-body table-responsive"><table class="table table-sm"><thead><tr><th>Date</th><th>Item</th><th>Qty (Base)</th><th>Type</th><th>Mess</th><th>Remarks</th><th></th></tr></thead><tbody>
            @foreach($issues as $i)<tr>
                <td>{{ $i->issue_date }}</td>
                <td>{{ $items->firstWhere('id',$i->item_id)?->name ?? $i->item_id }}</td>
                <td>{{ $i->quantity }}</td>
                <td>{{ $i->issue_type ?? 'CONSUMPTION' }}</td>
                <td>{{ $i->mess->name ?? '—' }}</td>
                <td>{{ $i->remarks }}</td>
                <td><form method="POST" action="{{ route('admin.kitchen.issues.approve.legacy',$i) }}">@csrf<button class="btn btn-sm btn-outline-success">Approve</button></form></td>
            </tr>@endforeach
        </tbody></table></div></div>
    </div>
</div>
@endsection

@php
    $kitchenItemsJson = $items->map(function ($i) {
        return [
            'id' => $i->id,
            'name' => $i->name,
            'base_uom' => $i->uom,
            'units' => $i->units->map(function ($u) {
                return [
                    'code' => $u->unit_code,
                    'factor' => (float) $u->factor_to_base,
                    'is_default_for_kitchen' => (bool) $u->is_default_for_kitchen,
                    'is_default_for_grn' => (bool) $u->is_default_for_grn,
                ];
            })->values()->all(),
        ];
    })->values()->all();
@endphp

@push('scripts')
<script>
    (() => {
        const items = @json($kitchenItemsJson);
        const itemsById = {};
        items.forEach((i) => { itemsById[i.id] = i; });

        const itemSelect = document.getElementById('kitchen-item-select');
        const unitSelect = document.getElementById('kitchen-unit-select');
        const qtyInput = document.getElementById('kitchen-qty-input');
        const preview = document.getElementById('kitchen-conversion-preview');

        const syncUnits = () => {
            const itemId = Number(itemSelect?.value || 0);
            const item = itemsById[itemId];
            if (!unitSelect || !item) {
                return;
            }

            const units = item.units || [];
            unitSelect.innerHTML = '';

            if (units.length === 0) {
                const opt = document.createElement('option');
                opt.value = '';
                opt.textContent = item.base_uom ? `Base unit (${item.base_uom})` : 'Base unit';
                unitSelect.appendChild(opt);
            } else {
                const defaultUnit = units.find(u => u.is_default_for_kitchen) || units.find(u => u.factor === 1) || units[0];
                const baseOpt = document.createElement('option');
                baseOpt.value = '';
                baseOpt.textContent = item.base_uom ? `Base unit (${item.base_uom})` : 'Base unit';
                unitSelect.appendChild(baseOpt);

                units.forEach((u) => {
                    const opt = document.createElement('option');
                    opt.value = u.code;
                    opt.textContent = `${u.code} (x${u.factor.toFixed(3)} ${item.base_uom})`;
                    if (defaultUnit && defaultUnit.code === u.code) {
                        opt.selected = true;
                    }
                    unitSelect.appendChild(opt);
                });
            }

            syncPreview();
        };

        const syncPreview = () => {
            if (!preview) return;
            const itemId = Number(itemSelect?.value || 0);
            const item = itemsById[itemId];
            const qty = Number(qtyInput?.value || 0);
            const unitCode = unitSelect?.value || '';

            if (!item || !qty || !unitCode) {
                preview.textContent = '';
                return;
            }

            const unit = (item.units || []).find(u => u.code === unitCode);
            if (!unit) {
                preview.textContent = '';
                return;
            }

            const baseQty = qty * unit.factor;
            preview.textContent = `${qty.toFixed(3)} ${unit.code} = ${baseQty.toFixed(3)} ${item.base_uom}`;
        };

        itemSelect?.addEventListener('change', syncUnits);
        unitSelect?.addEventListener('change', syncPreview);
        qtyInput?.addEventListener('input', syncPreview);

        syncUnits();
    })();
</script>
@endpush
