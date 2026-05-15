<div class="mb-3">
    <label class="form-label" for="vehicle_id">Vehicle</label>
    <input id="vehicle_id" name="vehicle_id" value="{{ old('vehicle_id', $record->vehicle_id ?? '') }}" class="form-control @error('vehicle_id') is-invalid @enderror">
    @error('vehicle_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="document_type">Document Type</label>
    <input id="document_type" name="document_type" value="{{ old('document_type', $record->document_type ?? '') }}" class="form-control @error('document_type') is-invalid @enderror">
    @error('document_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="document_no">Document No</label>
    <input id="document_no" name="document_no" value="{{ old('document_no', $record->document_no ?? '') }}" class="form-control @error('document_no') is-invalid @enderror">
    @error('document_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="issue_date">Issue Date</label>
    <input id="issue_date" name="issue_date" value="{{ old('issue_date', $record->issue_date ?? '') }}" class="form-control @error('issue_date') is-invalid @enderror">
    @error('issue_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="expiry_date">Expiry Date</label>
    <input id="expiry_date" name="expiry_date" value="{{ old('expiry_date', $record->expiry_date ?? '') }}" class="form-control @error('expiry_date') is-invalid @enderror">
    @error('expiry_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<div class="mb-3">
    <label class="form-label" for="status">Status</label>
    <input id="status" name="status" value="{{ old('status', $record->status ?? '') }}" class="form-control @error('status') is-invalid @enderror">
    @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
</div>
<button class="btn btn-primary" type="submit">Save</button>
<a class="btn btn-light" href="{{ url()->previous() }}">Cancel</a>
