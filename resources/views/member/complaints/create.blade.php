@extends('layouts.app')

@section('title', 'Submit Complaint / Suggestion')
@section('page_title', 'Submit Complaint / Suggestion')

@section('content')
<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('member.complaints.store') }}" class="row g-3">
            @csrf
            <div class="col-md-3"><select name="type" class="form-select" required>@foreach(['COMPLAINT','SUGGESTION'] as $v)<option value="{{ $v }}">{{ $v }}</option>@endforeach</select></div>
            <div class="col-md-3"><input name="category" class="form-control" placeholder="Category"></div>
            <div class="col-md-3"><select name="priority" class="form-select">@foreach(['LOW','NORMAL','HIGH','URGENT'] as $v)<option value="{{ $v }}" @selected($v==='NORMAL')>{{ $v }}</option>@endforeach</select></div>
            <div class="col-md-3"><input name="submitted_by_contact" class="form-control" placeholder="Contact"></div>
            <div class="col-12"><input name="subject" class="form-control" placeholder="Subject" required></div>
            <div class="col-12"><textarea name="description" class="form-control" rows="5" placeholder="Describe your issue or suggestion" required></textarea></div>
            <div class="col-12"><button class="btn btn-primary">Submit</button></div>
        </form>
    </div>
</div>
@endsection
