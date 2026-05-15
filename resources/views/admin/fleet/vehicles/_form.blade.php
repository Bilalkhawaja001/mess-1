<div class="mb-3">
    <label class="form-label" for="vehicle_no">Vehicle No</label>
    <input id="vehicle_no" name="vehicle_no" value="{{ old('vehicle_no', $record->vehicle_no ?? '') }}" class="form-control @error('vehicle_no') is-invalid @enderror">
    @error('vehicle_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="vehicle_type_id">Vehicle Type</label>
    <input id="vehicle_type_id" name="vehicle_type_id" value="{{ old('vehicle_type_id', $record->vehicle_type_id ?? '') }}" class="form-control @error('vehicle_type_id') is-invalid @enderror">
    @error('vehicle_type_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="make">Make</label>
    <input id="make" name="make" value="{{ old('make', $record->make ?? '') }}" class="form-control @error('make') is-invalid @enderror">
    @error('make')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="model">Model</label>
    <input id="model" name="model" value="{{ old('model', $record->model ?? '') }}" class="form-control @error('model') is-invalid @enderror">
    @error('model')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="year">Year</label>
    <input id="year" name="year" value="{{ old('year', $record->year ?? '') }}" class="form-control @error('year') is-invalid @enderror">
    @error('year')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="registration_no">Registration No</label>
    <input id="registration_no" name="registration_no" value="{{ old('registration_no', $record->registration_no ?? '') }}" class="form-control @error('registration_no') is-invalid @enderror">
    @error('registration_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="current_odometer">Current Odometer</label>
    <input id="current_odometer" name="current_odometer" value="{{ old('current_odometer', $record->current_odometer ?? '') }}" class="form-control @error('current_odometer') is-invalid @enderror">
    @error('current_odometer')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="fuel_type">Fuel Type</label>
    <input id="fuel_type" name="fuel_type" value="{{ old('fuel_type', $record->fuel_type ?? '') }}" class="form-control @error('fuel_type') is-invalid @enderror">
    @error('fuel_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="status">Status</label>
    <input id="status" name="status" value="{{ old('status', $record->status ?? '') }}" class="form-control @error('status') is-invalid @enderror">
    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<button class="btn btn-primary" type="submit">Save</button>
<a class="btn btn-light" href="{{ url()->previous() }}">Cancel</a>
