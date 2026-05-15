<div class="mb-3">
    <label class="form-label" for="vehicle_id">Vehicle</label>
    <input id="vehicle_id" name="vehicle_id" value="{{ old('vehicle_id', $record->vehicle_id ?? '') }}" class="form-control @error('vehicle_id') is-invalid @enderror">
    @error('vehicle_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="maintenance_date">Date</label>
    <input id="maintenance_date" name="maintenance_date" value="{{ old('maintenance_date', $record->maintenance_date ?? '') }}" class="form-control @error('maintenance_date') is-invalid @enderror">
    @error('maintenance_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="maintenance_type">Type</label>
    <input id="maintenance_type" name="maintenance_type" value="{{ old('maintenance_type', $record->maintenance_type ?? '') }}" class="form-control @error('maintenance_type') is-invalid @enderror">
    @error('maintenance_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="workshop">Workshop</label>
    <input id="workshop" name="workshop" value="{{ old('workshop', $record->workshop ?? '') }}" class="form-control @error('workshop') is-invalid @enderror">
    @error('workshop')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="odometer_reading">Odometer</label>
    <input id="odometer_reading" name="odometer_reading" value="{{ old('odometer_reading', $record->odometer_reading ?? '') }}" class="form-control @error('odometer_reading') is-invalid @enderror">
    @error('odometer_reading')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="description">Description</label>
    <input id="description" name="description" value="{{ old('description', $record->description ?? '') }}" class="form-control @error('description') is-invalid @enderror">
    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="parts_cost">Parts Cost</label>
    <input id="parts_cost" name="parts_cost" value="{{ old('parts_cost', $record->parts_cost ?? '') }}" class="form-control @error('parts_cost') is-invalid @enderror">
    @error('parts_cost')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="labour_cost">Labour Cost</label>
    <input id="labour_cost" name="labour_cost" value="{{ old('labour_cost', $record->labour_cost ?? '') }}" class="form-control @error('labour_cost') is-invalid @enderror">
    @error('labour_cost')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="status">Status</label>
    <input id="status" name="status" value="{{ old('status', $record->status ?? '') }}" class="form-control @error('status') is-invalid @enderror">
    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<button class="btn btn-primary" type="submit">Save</button>
<a class="btn btn-light" href="{{ url()->previous() }}">Cancel</a>
