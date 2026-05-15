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
    <label class="form-label" for="fuel_date">Fuel Date</label>
    <input id="fuel_date" name="fuel_date" value="{{ old('fuel_date', $record->fuel_date ?? '') }}" class="form-control @error('fuel_date') is-invalid @enderror">
    @error('fuel_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="fuel_station">Fuel Station</label>
    <input id="fuel_station" name="fuel_station" value="{{ old('fuel_station', $record->fuel_station ?? '') }}" class="form-control @error('fuel_station') is-invalid @enderror">
    @error('fuel_station')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="liters">Liters</label>
    <input id="liters" name="liters" value="{{ old('liters', $record->liters ?? '') }}" class="form-control @error('liters') is-invalid @enderror">
    @error('liters')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="rate_per_liter">Rate/Liter</label>
    <input id="rate_per_liter" name="rate_per_liter" value="{{ old('rate_per_liter', $record->rate_per_liter ?? '') }}" class="form-control @error('rate_per_liter') is-invalid @enderror">
    @error('rate_per_liter')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="odometer_reading">Odometer</label>
    <input id="odometer_reading" name="odometer_reading" value="{{ old('odometer_reading', $record->odometer_reading ?? '') }}" class="form-control @error('odometer_reading') is-invalid @enderror">
    @error('odometer_reading')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="is_full_tank">Full Tank</label>
    <input id="is_full_tank" name="is_full_tank" value="{{ old('is_full_tank', $record->is_full_tank ?? '') }}" class="form-control @error('is_full_tank') is-invalid @enderror">
    @error('is_full_tank')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<button class="btn btn-primary" type="submit">Save</button>
<a class="btn btn-light" href="{{ url()->previous() }}">Cancel</a>
