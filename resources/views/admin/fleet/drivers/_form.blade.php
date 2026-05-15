<div class="mb-3">
    <label class="form-label" for="name">Driver Name</label>
    <input id="name" name="name" value="{{ old('name', $record->name ?? '') }}" class="form-control @error('name') is-invalid @enderror">
    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="employee_code">Employee Code</label>
    <input id="employee_code" name="employee_code" value="{{ old('employee_code', $record->employee_code ?? '') }}" class="form-control @error('employee_code') is-invalid @enderror">
    @error('employee_code')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="cnic">CNIC</label>
    <input id="cnic" name="cnic" value="{{ old('cnic', $record->cnic ?? '') }}" class="form-control @error('cnic') is-invalid @enderror">
    @error('cnic')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="mobile">Mobile</label>
    <input id="mobile" name="mobile" value="{{ old('mobile', $record->mobile ?? '') }}" class="form-control @error('mobile') is-invalid @enderror">
    @error('mobile')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="license_no">License No</label>
    <input id="license_no" name="license_no" value="{{ old('license_no', $record->license_no ?? '') }}" class="form-control @error('license_no') is-invalid @enderror">
    @error('license_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="license_expiry">License Expiry</label>
    <input id="license_expiry" name="license_expiry" value="{{ old('license_expiry', $record->license_expiry ?? '') }}" class="form-control @error('license_expiry') is-invalid @enderror">
    @error('license_expiry')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="status">Status</label>
    <input id="status" name="status" value="{{ old('status', $record->status ?? '') }}" class="form-control @error('status') is-invalid @enderror">
    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<button class="btn btn-primary" type="submit">Save</button>
<a class="btn btn-light" href="{{ url()->previous() }}">Cancel</a>
