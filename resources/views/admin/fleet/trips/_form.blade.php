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
    <label class="form-label" for="trip_date">Trip Date</label>
    <input id="trip_date" name="trip_date" value="{{ old('trip_date', $record->trip_date ?? '') }}" class="form-control @error('trip_date') is-invalid @enderror">
    @error('trip_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="from_location">From</label>
    <input id="from_location" name="from_location" value="{{ old('from_location', $record->from_location ?? '') }}" class="form-control @error('from_location') is-invalid @enderror">
    @error('from_location')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="to_location">To</label>
    <input id="to_location" name="to_location" value="{{ old('to_location', $record->to_location ?? '') }}" class="form-control @error('to_location') is-invalid @enderror">
    @error('to_location')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="purpose">Purpose</label>
    <input id="purpose" name="purpose" value="{{ old('purpose', $record->purpose ?? '') }}" class="form-control @error('purpose') is-invalid @enderror">
    @error('purpose')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="start_odometer">Start Odometer</label>
    <input id="start_odometer" name="start_odometer" value="{{ old('start_odometer', $record->start_odometer ?? '') }}" class="form-control @error('start_odometer') is-invalid @enderror">
    @error('start_odometer')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="end_odometer">End Odometer</label>
    <input id="end_odometer" name="end_odometer" value="{{ old('end_odometer', $record->end_odometer ?? '') }}" class="form-control @error('end_odometer') is-invalid @enderror">
    @error('end_odometer')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<button class="btn btn-primary" type="submit">Save</button>
<a class="btn btn-light" href="{{ url()->previous() }}">Cancel</a>
