<div class="mb-3">
    <label class="form-label" for="vehicle_id">Vehicle</label>
    <input id="vehicle_id" name="vehicle_id" value="{{ old('vehicle_id', $record->vehicle_id ?? '') }}" class="form-control @error('vehicle_id') is-invalid @enderror">
    @error('vehicle_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="driver_id">Driver</label>
    <input id="driver_id" name="driver_id" value="{{ old('driver_id', $record->driver_id ?? '') }}" class="form-control @error('driver_id') is-invalid @enderror">
    @error('driver_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="incident_date">Incident Date</label>
    <input id="incident_date" name="incident_date" value="{{ old('incident_date', $record->incident_date ?? '') }}" class="form-control @error('incident_date') is-invalid @enderror">
    @error('incident_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="location">Location</label>
    <input id="location" name="location" value="{{ old('location', $record->location ?? '') }}" class="form-control @error('location') is-invalid @enderror">
    @error('location')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="severity">Severity</label>
    <input id="severity" name="severity" value="{{ old('severity', $record->severity ?? '') }}" class="form-control @error('severity') is-invalid @enderror">
    @error('severity')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="description">Description</label>
    <input id="description" name="description" value="{{ old('description', $record->description ?? '') }}" class="form-control @error('description') is-invalid @enderror">
    @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="estimated_cost">Estimated Cost</label>
    <input id="estimated_cost" name="estimated_cost" value="{{ old('estimated_cost', $record->estimated_cost ?? '') }}" class="form-control @error('estimated_cost') is-invalid @enderror">
    @error('estimated_cost')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="status">Status</label>
    <input id="status" name="status" value="{{ old('status', $record->status ?? '') }}" class="form-control @error('status') is-invalid @enderror">
    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<button class="btn btn-primary" type="submit">Save</button>
<a class="btn btn-light" href="{{ url()->previous() }}">Cancel</a>
