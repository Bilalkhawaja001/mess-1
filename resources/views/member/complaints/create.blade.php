@extends('layouts.app')

@section('title', 'Submit Complaint / Suggestion')
@section('page_title', 'Submit Complaint / Suggestion')

@section('content')
<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('member.complaints.store') }}" class="row g-3">
            @csrf
            <div class="col-md-4">
                <label class="form-label">Type</label>
                <select name="type" class="form-select" required>
                    @foreach(['COMPLAINT','SUGGESTION'] as $v)
                        <option value="{{ $v }}">{{ $v }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Message</label>
                <textarea name="message" class="form-control" rows="6" placeholder="Write your complaint or suggestion" required></textarea>
            </div>
            <div class="col-12"><button class="btn btn-primary">Submit</button></div>
        </form>
    </div>
</div>
@endsection
