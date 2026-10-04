@extends('layouts.app')

@section('title', 'Members')
@section('page_title', 'Members Management')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" rel="stylesheet">

<script src="https://cdn.tailwindcss.com"></script>
<script>
tailwind.config = {
    corePlugins: {
        preflight: false
    },
    theme: {
        extend: {
            colors: {
                primary: '#2a14b4',
                'primary-container': '#4338ca',
                'primary-fixed': '#e3dfff',

                secondary: '#006a61',
                'secondary-container': '#86f2e4',
                'on-secondary-container': '#006f66',

                surface: '#f8f9ff',
                'surface-container-lowest': '#ffffff',
                'surface-container-low': '#eff4ff',
                'surface-container': '#e5eeff',
                'surface-container-high': '#dce9ff',
                'surface-container-highest': '#d3e4fe',

                'on-surface': '#0b1c30',
                'on-surface-variant': '#464554',

                outline: '#777586',
                'outline-variant': '#c7c4d7',

                error: '#ba1a1a',
                'error-container': '#ffdad6'
            },

            fontFamily: {
                headline: ['Plus Jakarta Sans'],
                body: ['Inter'],
                code: ['JetBrains Mono']
            }
        }
    }
}
</script>

<style>





::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

::-webkit-scrollbar-thumb {
    background: #c7c4d7;
    border-radius: 8px;
}

.member-action-menu {
    display: none;
    position: fixed;
    z-index: 9999;
    width: 220px;
}

.member-action-menu.is-open {
    display: block;
}

.stitch-overlay {
    display: none;
}

.stitch-overlay.is-open {
    display: block;
}

body.modal-open {
    overflow: hidden;
}

</style>
@endpush

@section('content')

@php
$admin = auth()->user();
    $roleCode = optional($admin?->role)->code;

    $canResetPassword = in_array(
        $roleCode,
        ['SUPER_ADMIN', 'ADMIN'],
        true
    );

    $canPortalAccounts = $admin
        && $admin->hasPermission('superadmin.member_account_create');

    $linkedVisibleCount = $rows
        ->filter(fn($member) => !empty($member->user_id))
        ->count();
@endphp

<div class="members-stitch-page">
    <div class="max-w-[1600px] mx-auto px-6 py-6">



{{-- FLASH MESSAGES --}}

@if(session('generated_password'))
<div class="flex items-center justify-between p-4 mb-5 rounded-xl bg-secondary-container text-on-secondary-container shadow-sm">
    <div class="flex items-center gap-3">

        <span class="material-symbols-outlined text-secondary">
            key
        </span>

        <div class="text-[13px]">
            <strong>
                Portal account created for {{ session('generated_for') }}
            </strong>

            <div class="mt-1">
                Username:
                <span class="font-code font-semibold">
                    {{ session('generated_username') }}
                </span>

                &nbsp; Password:
                <span
                    id="generatedPassword"
                    class="font-code font-semibold">
                    {{ session('generated_password') }}
                </span>

                <button
                    type="button"
                    onclick="navigator.clipboard.writeText(document.getElementById('generatedPassword').textContent)"
                    class="ml-2 px-2 py-1 rounded bg-white/50 text-[11px]">
                    Copy
                </button>
            </div>
        </div>

    </div>
</div>
@endif

@if(session('success'))
<div class="flex items-center gap-3 p-4 mb-5 rounded-xl bg-secondary-container text-on-secondary-container shadow-sm">
    <span class="material-symbols-outlined text-secondary">
        check_circle
    </span>

    <div class="text-[13px] font-medium">
        {{ session('success') }}
    </div>
</div>
@endif

@if(session('error'))
<div class="flex items-center gap-3 p-4 mb-5 rounded-xl bg-error-container text-error shadow-sm">
    <span class="material-symbols-outlined">
        error
    </span>

    <div class="text-[13px] font-medium">
        {{ session('error') }}
    </div>
</div>
@endif

@if($errors->any())
<div class="p-4 mb-5 rounded-xl bg-error-container text-error shadow-sm text-[13px]">
    <div class="font-semibold mb-1">
        Please correct the following:
    </div>

    <ul class="list-disc ml-5">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif

{{-- ====================================================== --}}
{{-- TABS / STATS                                            --}}
{{-- ====================================================== --}}

<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">

    <div class="flex items-center gap-2 bg-surface-container-low p-1 rounded-xl shadow-sm">

        <a
            href="{{ route('admin.members.index', [
                'status' => 'active',
                'q' => $q
            ]) }}"
            class="flex items-center gap-2 px-4 py-2 rounded-lg no-underline transition-all
            {{ ($status ?? 'active') !== 'inactive'
                ? 'bg-white text-primary shadow-sm'
                : 'text-on-surface-variant' }}">

            <span class="w-2 h-2 rounded-full bg-secondary"></span>

            <span class="text-[13px] font-medium">
                Active Members
            </span>

            <span class="px-2 py-0.5 rounded-full bg-secondary-container text-on-secondary-container font-code text-[11px] font-semibold">
                {{ $activeCount ?? 0 }}
            </span>
        </a>

        <a
            href="{{ route('admin.members.index', [
                'status' => 'inactive',
                'q' => $q
            ]) }}"
            class="flex items-center gap-2 px-4 py-2 rounded-lg no-underline transition-all
            {{ ($status ?? '') === 'inactive'
                ? 'bg-white text-primary shadow-sm'
                : 'text-on-surface-variant' }}">

            <span class="w-2 h-2 rounded-full bg-outline"></span>

            <span class="text-[13px] font-medium">
                Inactive Members
            </span>

            <span class="px-2 py-0.5 rounded-full bg-surface-container-high text-on-surface-variant font-code text-[11px] font-semibold">
                {{ $inactiveCount ?? 0 }}
            </span>
        </a>

    </div>

    <div class="flex flex-wrap items-center gap-4">

        <div class="flex items-center gap-3 px-4 py-2 bg-white rounded-xl shadow-sm">

            <div class="w-8 h-8 rounded-lg bg-surface-container-low flex items-center justify-center text-primary">
                <span class="material-symbols-outlined text-[18px]">
                    restaurant
                </span>
            </div>

            <div>
                <div class="text-[10px] text-on-surface-variant">
                    Allocated Messes
                </div>

                <div class="font-headline text-[15px] font-bold">
                    {{ $messes->count() }}
                </div>
            </div>

        </div>

        <div class="flex items-center gap-3 px-4 py-2 bg-white rounded-xl shadow-sm">

            <div class="w-8 h-8 rounded-lg bg-surface-container-low flex items-center justify-center text-secondary">
                <span class="material-symbols-outlined text-[18px]">
                    verified_user
                </span>
            </div>

            <div>
                <div class="text-[10px] text-on-surface-variant">
                    Linked Portal Users
                </div>

                <div class="font-headline text-[15px] font-bold">
                    {{ $linkedVisibleCount }} / {{ $rows->count() }}
                </div>
            </div>

        </div>

    </div>
</div>

{{-- ====================================================== --}}
{{-- COMMAND BAR                                             --}}
{{-- ====================================================== --}}

<div class="bg-white p-4 rounded-xl shadow-sm mb-6 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-4">

    <form
        method="GET"
        action="{{ route('admin.members.index') }}"
        class="flex-1 flex items-center gap-2 max-w-xl">

        <input
            type="hidden"
            name="status"
            value="{{ $status ?? 'active' }}">

        <div class="relative w-full">

            <input
                type="text"
                name="q"
                value="{{ $q ?? '' }}"
                class="w-full bg-surface-container-low pl-4 pr-4 py-2 rounded-xl text-[13px] outline-none focus:bg-white focus:ring-2 focus:ring-primary"
                placeholder="Filter by Code, Name, Department, or Mobile...">

        </div>

        <button
            type="submit"
            class="px-4 py-2 bg-surface-container text-on-surface rounded-xl text-[13px] font-medium">
            Search
        </button>

        <a
            href="{{ route('admin.members.index', [
                'status' => $status ?? 'active'
            ]) }}"
            class="px-3 py-2 text-on-surface-variant hover:text-on-surface text-[13px] no-underline">
            Reset
        </a>
    </form>

    <div class="flex items-center gap-2">

        @if($canPortalAccounts)
        <button
            type="button"
            onclick="openCreatePortalAccount()"
            class="px-4 py-2 rounded-xl bg-surface-container-low text-on-surface hover:text-primary text-[13px] font-medium flex items-center gap-2">

            <span class="material-symbols-outlined text-[18px]">
                manage_accounts
            </span>

            Create Portal Account
        </button>
        @endif

        <button
            type="button"
            onclick="openStitchModal('importModal')"
            class="px-4 py-2 rounded-xl bg-surface-container-low text-on-surface hover:text-primary text-[13px] font-medium flex items-center gap-2">

            <span class="material-symbols-outlined text-[18px]">
                upload_file
            </span>

            Import Members
        </button>

        <button
            type="button"
            onclick="openStitchModal('createDrawer')"
            class="px-4 py-2 rounded-xl bg-primary text-white hover:bg-primary-container text-[13px] font-medium flex items-center gap-2">

            <span class="material-symbols-outlined text-[18px]">
                person_add
            </span>

            Add Member
        </button>

    </div>

</div>

{{-- ====================================================== --}}
{{-- MEMBER TABLE                                            --}}
{{-- ====================================================== --}}

<div class="bg-white rounded-xl shadow-sm">

<div class="overflow-x-auto w-full">

<table class="w-full text-left min-w-[1100px]">

<thead>
<tr class="bg-surface-container-low text-on-surface-variant text-[10px] uppercase tracking-wider">

    <th class="px-4 py-3.5">Member Details</th>
    <th class="px-4 py-3.5">Department</th>
    <th class="px-4 py-3.5">Mess Allocation</th>
    <th class="px-4 py-3.5">Mobile</th>
    <th class="px-4 py-3.5">Join & Leave Dates</th>
    <th class="px-4 py-3.5">Portal User</th>
    <th class="px-4 py-3.5">Status</th>
    <th class="px-4 py-3.5 text-right">Actions</th>

</tr>
</thead>

<tbody class="divide-y divide-surface-container">

@forelse($rows as $m)

@php
    $nameParts = preg_split(
        '/\s+/',
        trim((string) $m->name)
    );

    $initials = collect($nameParts)
        ->filter()
        ->take(2)
        ->map(
            fn($part) =>
                mb_strtoupper(
                    mb_substr($part, 0, 1)
                )
        )
        ->implode('');

    $canDelete = (bool) (
        $removalMeta[$m->id]['can_delete']
        ?? false
    );

    $removeMessage =
        $removalMeta[$m->id]['message']
        ?? 'Are you sure?';

    $portalPayload = base64_encode(
        json_encode([
            'id' => $m->id,
            'code' => $m->member_code,
            'name' => $m->name,
            'mobile' => $m->mobile_number,
            'portal_enabled' => (bool) $m->portal_enabled,
            'mobile_verified' => optional($m->mobile_verified_at)->format('Y-m-d H:i'),
            'user_id' => $m->user?->id,
            'username' => $m->user?->username,
            'email' => $m->user?->email,
            'user_active' => (bool) ($m->user?->is_active ?? false),
            'last_login' => $m->user?->last_login_at
                ? \Illuminate\Support\Carbon::parse($m->user->last_login_at)->format('Y-m-d H:i')
                : null,
        ])
    );

    $editPayload = base64_encode(
        json_encode([
            'id' => $m->id,
            'code' => $m->member_code,
            'name' => $m->name,
            'department' => $m->department_name,
            'mess' => $m->mess_id,
            'mobile' => $m->mobile_number,
            'join' => optional($m->join_date)->format('Y-m-d'),
            'leave' => optional($m->leave_date)->format('Y-m-d'),
            'user' => $m->user_id,
            'active' => (bool) $m->is_active,
        ])
    );
@endphp

<tr class="hover:bg-surface-container-lowest transition-colors">

<td class="px-4 py-3">

    <div class="flex items-center gap-3">

        <div class="w-9 h-9 rounded-full bg-primary-fixed flex items-center justify-center text-primary font-headline font-bold flex-shrink-0">
            {{ $initials ?: '?' }}
        </div>

        <div class="min-w-0">

            <div class="font-headline text-[13px] font-semibold truncate">
                {{ $m->name }}
            </div>

            <div class="font-code text-[11px] text-primary font-medium">
                {{ $m->member_code }}
            </div>

        </div>

    </div>

</td>

<td class="px-4 py-3 text-[13px]">
    {{ $m->department_name ?: '-' }}
</td>

<td class="px-4 py-3">

    @if($m->mess)

        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-surface-container text-[12px]">

            <span class="material-symbols-outlined text-[16px] text-primary">
                restaurant_menu
            </span>

            {{ $m->mess->name }}

        </span>

        <div class="text-[10px] text-on-surface-variant mt-1">
            {{ $m->mess->code }}
        </div>

    @else
        <span class="text-outline text-[12px]">
            No Mess
        </span>
    @endif

</td>

<td class="px-4 py-3">

    <div class="flex items-center gap-1.5">

        <span class="font-code text-[11px]">
            {{ $m->mobile_number ?: '-' }}
        </span>

        @if($m->mobile_verified_at)
            <span
                class="material-symbols-outlined text-secondary text-[16px]"
                title="Mobile Verified">
                verified
            </span>
        @endif

    </div>

</td>

<td class="px-4 py-3 text-[11px] font-code">

    <div>
        {{ optional($m->join_date)->format('Y-m-d') ?: '-' }}
    </div>

    <div class="{{ $m->leave_date ? 'text-error' : 'text-on-surface-variant italic' }}">
        {{ optional($m->leave_date)->format('Y-m-d') ?: 'Indefinite' }}
    </div>

</td>

<td class="px-4 py-3">

    @if($m->user)

        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-surface-container-high text-[11px] font-code">

            <span class="material-symbols-outlined text-[14px]">
                link
            </span>

            {{ $m->user->username }}

        </span>

        <div class="text-[10px] text-on-surface-variant mt-1">
            uid: #{{ $m->user->id }}
        </div>

    @else

        <span class="inline-flex px-2.5 py-0.5 rounded-full bg-surface-container text-outline text-[11px]">
            Unlinked
        </span>

    @endif

</td>

<td class="px-4 py-3">

    @if($m->is_active)

        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-secondary-container text-on-secondary-container text-[11px] font-semibold">

            <span class="w-1.5 h-1.5 rounded-full bg-secondary"></span>

            Active
        </span>

    @else

        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-surface-container text-outline text-[11px] font-semibold">

            <span class="w-1.5 h-1.5 rounded-full bg-outline"></span>

            Inactive
        </span>

    @endif

</td>

<td class="px-4 py-3 text-right">

<div class="flex items-center justify-end gap-1.5">

    <form
        method="POST"
        action="{{ route('admin.members.toggle-active', $m->id) }}">
        @csrf

        <button
            type="submit"
            title="Toggle Active Status"
            class="p-1.5 rounded-lg text-secondary hover:bg-secondary-container">

            <span class="material-symbols-outlined text-[18px]">
                {{ $m->is_active ? 'toggle_on' : 'toggle_off' }}
            </span>

        </button>
    </form>

    <button
        type="button"
        data-member="{{ $editPayload }}"
        onclick="openEditMember(this)"
        class="p-1.5 rounded-lg text-on-surface-variant hover:text-primary hover:bg-surface-container"
        title="Edit Member">

        <span class="material-symbols-outlined text-[18px]">
            edit_square
        </span>

    </button>

    @if($canPortalAccounts)
    <button
        type="button"
        data-portal="{{ $portalPayload }}"
        onclick="openPortalAccess(this)"
        class="p-1.5 rounded-lg text-on-surface-variant hover:text-primary hover:bg-surface-container"
        title="Portal Access">

        <span class="material-symbols-outlined text-[18px]">
            shield_person
        </span>

    </button>
    @endif

    <button
        type="button"
        onclick="toggleMemberMenu(
            'memberMenu{{ $m->id }}',
            this,
            event
        )"
        class="p-1.5 rounded-lg text-on-surface-variant hover:bg-surface-container">

        <span class="material-symbols-outlined text-[18px]">
            more_vert
        </span>

    </button>

    <div
        id="memberMenu{{ $m->id }}"
        class="member-action-menu rounded-xl bg-white shadow-xl py-1">

        @if($canResetPassword && $m->user)

            <button
                type="button"
                onclick="openResetPassword(
                    {{ $m->id }},
                    @js($m->name),
                    @js($m->member_code)
                )"
                class="w-full text-left px-4 py-2 text-[12px] hover:bg-surface-container-low">

                Reset Password
            </button>

        @endif

        @if($m->is_active)

            <form
                method="POST"
                action="{{ route('admin.members.deactivate', $m->id) }}">
                @csrf

                <button
                    type="submit"
                    class="w-full text-left px-4 py-2 text-[12px] hover:bg-surface-container-low">
                    Deactivate Member
                </button>
            </form>

        @else

            <form
                method="POST"
                action="{{ route('admin.members.reactivate', $m->id) }}">
                @csrf

                <button
                    type="submit"
                    class="w-full text-left px-4 py-2 text-[12px] hover:bg-surface-container-low">
                    Reactivate Member
                </button>
            </form>

        @endif

        <div class="h-px bg-surface-container my-1"></div>

        <button
            type="button"
            onclick="openRemoveMember(
                {{ $m->id }},
                @js($m->name),
                @js($m->member_code),
                {{ $canDelete ? 'true' : 'false' }},
                @js($removeMessage)
            )"
            class="w-full text-left px-4 py-2 text-[12px] {{ $canDelete ? 'text-error' : 'text-on-surface' }} hover:bg-error-container">

            {{ $canDelete
                ? 'Delete Member'
                : 'Remove / Deactivate' }}

        </button>

    </div>

</div>

</td>
</tr>

@empty

<tr>
<td colspan="8" class="px-4 py-16 text-center text-on-surface-variant">
    No members found.
</td>
</tr>

@endforelse

</tbody>
</table>

</div>

<div class="px-6 py-4 bg-surface-container-low flex items-center justify-between text-[12px] text-on-surface-variant">

    <span>
        Showing
        <strong class="text-on-surface">
            {{ $rows->count() }}
        </strong>
        member record(s)
    </span>

    <span class="font-code">
        {{ ($status ?? 'active') === 'inactive'
            ? 'Inactive'
            : 'Active' }}
    </span>

</div>

</div>

    </div>

{{-- ====================================================== --}}
{{-- CREATE MEMBER DRAWER                                   --}}
{{-- ====================================================== --}}

<div
    id="createDrawer"
    class="stitch-overlay fixed inset-0 z-[10000]">

    <div
        class="fixed inset-0 bg-[#213145]/30 backdrop-blur-sm"
        onclick="closeStitchModal('createDrawer')">
    </div>

    <div class="fixed inset-y-0 right-0 max-w-lg w-full bg-white shadow-2xl flex flex-col z-10">

        <div class="px-6 py-4 bg-surface-container-low flex items-center justify-between">

            <div>
                <h3 class="font-headline text-[20px] font-bold">
                    New Member Onboarding
                </h3>

                <p class="text-[12px] text-on-surface-variant">
                    Register member with mess assignment
                </p>
            </div>

            <button
                type="button"
                onclick="closeStitchModal('createDrawer')">

                <span class="material-symbols-outlined">
                    close
                </span>
            </button>

        </div>

        <form
            method="POST"
            action="{{ route('admin.members.store') }}"
            class="flex-1 overflow-y-auto p-6">
            @csrf

            <div class="space-y-4">

                <div>
                    <label class="block text-[13px] font-medium mb-1">
                        Member Code *
                    </label>

                    <input
                        name="member_code"
                        value="{{ old('member_code') }}"
                        required
                        class="w-full bg-surface-container-low px-3 py-2 rounded-lg outline-none focus:ring-2 focus:ring-primary">
                </div>

                <div>
                    <label class="block text-[13px] font-medium mb-1">
                        Full Name *
                    </label>

                    <input
                        name="name"
                        value="{{ old('name') }}"
                        required
                        class="w-full bg-surface-container-low px-3 py-2 rounded-lg outline-none focus:ring-2 focus:ring-primary">
                </div>

                <div>
                    <label class="block text-[13px] font-medium mb-1">
                        Department
                    </label>

                    <input
                        name="department_name"
                        value="{{ old('department_name') }}"
                        class="w-full bg-surface-container-low px-3 py-2 rounded-lg outline-none focus:ring-2 focus:ring-primary">
                </div>

                <div>
                    <label class="block text-[13px] font-medium mb-1">
                        Mess
                    </label>

                    <select
                        name="mess_id"
                        class="w-full bg-surface-container-low px-3 py-2 rounded-lg outline-none focus:ring-2 focus:ring-primary">

                        <option value="">
                            No Mess
                        </option>

                        @foreach($messes as $mess)

                            <option
                                value="{{ $mess->id }}"
                                @selected(
                                    (string) old('mess_id')
                                    ===
                                    (string) $mess->id
                                )>

                                {{ $mess->name }}
                                ({{ $mess->code }})

                            </option>

                        @endforeach

                    </select>
                </div>

                <div>
                    <label class="block text-[13px] font-medium mb-1">
                        Mobile Number
                    </label>

                    <input
                        name="mobile_number"
                        value="{{ old('mobile_number') }}"
                        class="w-full bg-surface-container-low px-3 py-2 rounded-lg outline-none focus:ring-2 focus:ring-primary">
                </div>

                <div class="grid grid-cols-2 gap-4">

                    <div>
                        <label class="block text-[13px] font-medium mb-1">
                            Join Date *
                        </label>

                        <input
                            type="date"
                            name="join_date"
                            value="{{ old('join_date') }}"
                            required
                            class="w-full bg-surface-container-low px-3 py-2 rounded-lg outline-none focus:ring-2 focus:ring-primary">
                    </div>

                    <div>
                        <label class="block text-[13px] font-medium mb-1">
                            Leave Date
                        </label>

                        <input
                            type="date"
                            name="leave_date"
                            value="{{ old('leave_date') }}"
                            class="w-full bg-surface-container-low px-3 py-2 rounded-lg outline-none focus:ring-2 focus:ring-primary">
                    </div>

                </div>

                <div>
                    <label class="block text-[13px] font-medium mb-1">
                        Link to System Account
                    </label>

                    <select
                        name="user_id"
                        class="w-full bg-surface-container-low px-3 py-2 rounded-lg outline-none focus:ring-2 focus:ring-primary">

                        <option value="">
                            Unassigned
                        </option>

                        @foreach($users as $u)

                            <option
                                value="{{ $u->id }}"
                                @selected(
                                    (string) old('user_id')
                                    ===
                                    (string) $u->id
                                )>

                                {{ $u->username }}
                                ({{ $u->role->code ?? '-' }})

                            </option>

                        @endforeach

                    </select>
                </div>

                <label class="flex items-center gap-3 pt-2 text-[13px]">

                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        @checked(old('is_active', 1))>

                    Active Member
                </label>

            </div>

            <div class="pt-8 flex items-center justify-end gap-2">

                <button
                    type="button"
                    onclick="closeStitchModal('createDrawer')"
                    class="px-4 py-2 rounded-xl bg-surface-container-low text-[13px]">
                    Cancel
                </button>

                <button
                    type="submit"
                    class="px-5 py-2 rounded-xl bg-primary text-white text-[13px] flex items-center gap-2">

                    <span class="material-symbols-outlined text-[18px]">
                        check
                    </span>

                    Save Member
                </button>

            </div>

        </form>

    </div>
</div>

{{-- ====================================================== --}}
{{-- EDIT DRAWER                                             --}}
{{-- ====================================================== --}}

<div
    id="editDrawer"
    class="stitch-overlay fixed inset-0 z-[10000]">

    <div
        class="fixed inset-0 bg-[#213145]/30 backdrop-blur-sm"
        onclick="closeStitchModal('editDrawer')">
    </div>

    <div class="fixed inset-y-0 right-0 max-w-lg w-full bg-white shadow-2xl flex flex-col z-10">

        <div class="px-6 py-4 bg-surface-container-low flex justify-between">

            <div>
                <h3 class="font-headline text-[20px] font-bold">
                    Edit Member Record
                </h3>

                <p
                    id="editSubtitle"
                    class="text-[12px] text-on-surface-variant">
                </p>
            </div>

            <button
                type="button"
                onclick="closeStitchModal('editDrawer')">

                <span class="material-symbols-outlined">
                    close
                </span>
            </button>

        </div>

        <form
            method="POST"
            action=""
            id="editMemberForm"
            class="flex-1 overflow-y-auto p-6">

            @csrf
            @method('PUT')

            <div class="space-y-4">

                <input
                    id="editCode"
                    name="member_code"
                    required
                    class="w-full bg-surface-container-low px-3 py-2 rounded-lg"
                    placeholder="Member Code">

                <input
                    id="editName"
                    name="name"
                    required
                    class="w-full bg-surface-container-low px-3 py-2 rounded-lg"
                    placeholder="Name">

                <input
                    id="editDepartment"
                    name="department_name"
                    class="w-full bg-surface-container-low px-3 py-2 rounded-lg"
                    placeholder="Department">

                <select
                    id="editMess"
                    name="mess_id"
                    class="w-full bg-surface-container-low px-3 py-2 rounded-lg">

                    <option value="">
                        No Mess
                    </option>

                    @foreach($messes as $mess)

                        <option value="{{ $mess->id }}">
                            {{ $mess->name }}
                            ({{ $mess->code }})
                        </option>

                    @endforeach

                </select>

                <input
                    id="editMobile"
                    name="mobile_number"
                    class="w-full bg-surface-container-low px-3 py-2 rounded-lg"
                    placeholder="Mobile">

                <div class="grid grid-cols-2 gap-4">

                    <input
                        type="date"
                        id="editJoin"
                        name="join_date"
                        required
                        class="w-full bg-surface-container-low px-3 py-2 rounded-lg">

                    <input
                        type="date"
                        id="editLeave"
                        name="leave_date"
                        class="w-full bg-surface-container-low px-3 py-2 rounded-lg">

                </div>

                <select
                    id="editUser"
                    name="user_id"
                    class="w-full bg-surface-container-low px-3 py-2 rounded-lg">

                    <option value="">
                        Unassigned
                    </option>

                    @foreach($users as $u)

                        <option value="{{ $u->id }}">
                            {{ $u->username }}
                        </option>

                    @endforeach

                </select>

                <label class="flex items-center gap-3 text-[13px]">

                    <input
                        type="checkbox"
                        id="editActive"
                        name="is_active"
                        value="1">

                    Active Member
                </label>

            </div>

            <div class="pt-8 flex justify-end gap-2">

                <button
                    type="button"
                    onclick="closeStitchModal('editDrawer')"
                    class="px-4 py-2 rounded-xl bg-surface-container-low text-[13px]">
                    Cancel
                </button>

                <button
                    type="submit"
                    class="px-5 py-2 rounded-xl bg-primary text-white text-[13px]">
                    Update Record
                </button>

            </div>

        </form>

    </div>
</div>

{{-- ====================================================== --}}
{{-- IMPORT MODAL                                            --}}
{{-- ====================================================== --}}

<div
    id="importModal"
    class="stitch-overlay fixed inset-0 z-[10000]">

    <div
        class="fixed inset-0 bg-[#213145]/30 backdrop-blur-sm"
        onclick="closeStitchModal('importModal')">
    </div>

    <div class="fixed inset-0 flex items-center justify-center p-4 z-10">

        <div class="w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden">

            <div class="px-6 py-4 bg-surface-container-low flex items-center justify-between">

                <div class="flex items-center gap-2">

                    <span class="material-symbols-outlined text-primary">
                        upload_file
                    </span>

                    <h3 class="font-headline text-[20px] font-bold">
                        Import Members Bulk Data
                    </h3>

                </div>

                <button
                    type="button"
                    onclick="closeStitchModal('importModal')">

                    <span class="material-symbols-outlined">
                        close
                    </span>
                </button>

            </div>

            <form
                method="POST"
                action="{{ route('admin.members.import') }}"
                enctype="multipart/form-data"
                class="p-6">

                @csrf

                <div class="flex items-center justify-between p-3 rounded-xl bg-surface-container-low mb-4">

                    <span class="text-[12px] font-medium">
                        Download CSV Template
                    </span>

                    <a
                        href="{{ route('admin.members.sample-csv') }}"
                        class="px-3 py-1 rounded-lg bg-surface-container text-primary text-[11px] font-semibold no-underline">

                        Download
                    </a>

                </div>

                <div class="relative p-8 rounded-xl bg-surface-container-low text-center">

                    <input
                        type="file"
                        name="file"
                        accept=".csv,.txt"
                        required
                        class="absolute inset-0 opacity-0 cursor-pointer"
                        onchange="
                            document.getElementById('fileNamePreview').textContent =
                            this.files[0]
                            ? this.files[0].name
                            : 'Accepted: .csv, .txt'
                        ">

                    <span class="material-symbols-outlined text-[36px] text-primary">
                        cloud_upload
                    </span>

                    <div class="text-[13px] font-medium mt-2">
                        Click or drag CSV/TXT file here
                    </div>

                    <div
                        id="fileNamePreview"
                        class="font-code text-[11px] text-secondary mt-1">
                        Accepted: .csv, .txt
                    </div>

                </div>

                <div class="text-[10px] text-on-surface-variant mt-4">
                    member_code, name, department_name,
                    mess_code, mobile_number, join_date,
                    leave_date, username, is_active
                </div>

                <div class="pt-5 flex justify-end gap-2">

                    <button
                        type="button"
                        onclick="closeStitchModal('importModal')"
                        class="px-4 py-2 rounded-xl bg-surface-container-low text-[13px]">
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="px-5 py-2 rounded-xl bg-primary text-white text-[13px]">
                        Execute Import
                    </button>

                </div>

            </form>

        </div>
    </div>
</div>

{{-- ====================================================== --}}
{{-- REMOVE MODAL                                            --}}
{{-- ====================================================== --}}

<div
    id="removeModal"
    class="stitch-overlay fixed inset-0 z-[10000]">

    <div
        class="fixed inset-0 bg-[#213145]/30 backdrop-blur-sm"
        onclick="closeStitchModal('removeModal')">
    </div>

    <div class="fixed inset-0 flex items-center justify-center p-4 z-10">

        <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl p-6">

            <div class="w-12 h-12 rounded-xl bg-error-container text-error flex items-center justify-center mb-4">

                <span class="material-symbols-outlined text-[28px]">
                    warning
                </span>

            </div>

            <h3
                id="removeTitle"
                class="font-headline text-[20px] font-bold">
                Confirm Removal
            </h3>

            <p
                id="removeTarget"
                class="font-code text-[11px] text-primary mt-1">
            </p>

            <div
                id="removeMessage"
                class="p-3 rounded-xl bg-surface-container-low mt-4 text-[12px]">
            </div>

            <form
                method="POST"
                action=""
                id="removeForm">
                @csrf

                <div class="pt-5 flex justify-end gap-2">

                    <button
                        type="button"
                        onclick="closeStitchModal('removeModal')"
                        class="px-4 py-2 rounded-xl bg-surface-container-low text-[13px]">
                        Cancel
                    </button>

                    <button
                        type="submit"
                        id="removeSubmit"
                        class="px-5 py-2 rounded-xl bg-error text-white text-[13px]">
                        Confirm
                    </button>

                </div>
            </form>

        </div>
    </div>
</div>

{{-- ====================================================== --}}
{{-- RESET PASSWORD                                          --}}
{{-- ====================================================== --}}

@if($canResetPassword)

<div
    id="resetModal"
    class="stitch-overlay fixed inset-0 z-[10000]">

    <div
        class="fixed inset-0 bg-[#213145]/30 backdrop-blur-sm"
        onclick="closeStitchModal('resetModal')">
    </div>

    <div class="fixed inset-0 flex items-center justify-center p-4 z-10">

        <div class="w-full max-w-md bg-white rounded-2xl shadow-2xl p-6">

            <div class="w-12 h-12 rounded-xl bg-surface-container-high text-primary flex items-center justify-center">

                <span class="material-symbols-outlined text-[28px]">
                    lock_reset
                </span>

            </div>

            <h3 class="font-headline text-[20px] font-bold mt-4">
                Reset Member Password
            </h3>

            <p
                id="resetTarget"
                class="text-[12px] text-on-surface-variant mt-1">
            </p>

            <form
                method="POST"
                action=""
                id="resetForm"
                class="mt-4">

                @csrf

                <label class="block text-[12px] font-medium mb-1">
                    New Password
                </label>

                <input
                    type="password"
                    name="new_password"
                    required
                    class="w-full bg-surface-container-low px-3 py-2 rounded-lg">

                <label class="block text-[12px] font-medium mt-3 mb-1">
                    Confirm Password
                </label>

                <input
                    type="password"
                    name="new_password_confirmation"
                    required
                    class="w-full bg-surface-container-low px-3 py-2 rounded-lg">

                <div class="pt-5 flex justify-end gap-2">

                    <button
                        type="button"
                        onclick="closeStitchModal('resetModal')"
                        class="px-4 py-2 rounded-xl bg-surface-container-low text-[13px]">
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="px-5 py-2 rounded-xl bg-primary text-white text-[13px]">
                        Reset Password
                    </button>

                </div>

            </form>

        </div>
    </div>
</div>

@endif


@if($canPortalAccounts)

{{-- ====================================================== --}}
{{-- CREATE PORTAL ACCOUNT                                  --}}
{{-- ====================================================== --}}

<div
    id="createPortalModal"
    class="stitch-overlay fixed inset-0 z-[10000]">

    <div
        class="fixed inset-0 bg-[#213145]/30 backdrop-blur-sm"
        onclick="closeStitchModal('createPortalModal')">
    </div>

    <div class="fixed inset-0 flex items-center justify-center p-4 z-10">

        <div class="w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden">

            <div class="px-6 py-4 bg-surface-container-low flex items-center justify-between">

                <div>
                    <h3 class="font-headline text-[20px] font-bold">
                        Create Portal Account
                    </h3>

                    <p class="text-[12px] text-on-surface-variant">
                        Enable member login access
                    </p>
                </div>

                <button
                    type="button"
                    onclick="closeStitchModal('createPortalModal')">

                    <span class="material-symbols-outlined">
                        close
                    </span>
                </button>

            </div>

            <form
                method="POST"
                action="{{ route('admin.member-accounts.store') }}"
                class="p-6">

                @csrf

                <div class="space-y-4">

                    <div>
                        <label class="block text-[12px] font-medium mb-1">
                            Member *
                        </label>

                        <select
                            name="member_id"
                            id="portalCreateMember"
                            required
                            class="w-full bg-surface-container-low px-3 py-2 rounded-lg">

                            <option value="">
                                Select Member
                            </option>

                            @foreach($rows as $portalMember)
                                @if($portalMember->is_active && !$portalMember->user_id)

                                <option value="{{ $portalMember->id }}">
                                    {{ $portalMember->member_code }}
                                    - {{ $portalMember->name }}
                                </option>

                                @endif
                            @endforeach

                        </select>
                    </div>

                    <div>
                        <label class="block text-[12px] font-medium mb-1">
                            Email
                        </label>

                        <input
                            type="email"
                            name="email"
                            class="w-full bg-surface-container-low px-3 py-2 rounded-lg"
                            placeholder="Optional — auto email used if blank">
                    </div>

                    <div>
                        <label class="block text-[12px] font-medium mb-1">
                            Password
                        </label>

                        <input
                            type="password"
                            name="password"
                            class="w-full bg-surface-container-low px-3 py-2 rounded-lg"
                            placeholder="Leave blank to auto-generate">
                    </div>

                    <div>
                        <label class="block text-[12px] font-medium mb-1">
                            Confirm Password
                        </label>

                        <input
                            type="password"
                            name="password_confirmation"
                            class="w-full bg-surface-container-low px-3 py-2 rounded-lg">
                    </div>

                    <label class="flex items-center gap-3 text-[12px]">

                        <input
                            type="checkbox"
                            name="force_password_change"
                            value="1">

                        Force password change on next login
                    </label>

                    <label class="flex items-center gap-3 text-[12px]">

                        <input
                            type="checkbox"
                            name="mark_mobile_verified"
                            value="1">

                        Mark mobile verified
                    </label>

                </div>

                <div class="pt-6 flex justify-end gap-2">

                    <button
                        type="button"
                        onclick="closeStitchModal('createPortalModal')"
                        class="px-4 py-2 rounded-xl bg-surface-container-low text-[13px]">
                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="px-5 py-2 rounded-xl bg-primary text-white text-[13px]">
                        Create Account
                    </button>

                </div>

            </form>

        </div>
    </div>
</div>

{{-- ====================================================== --}}
{{-- PORTAL ACCESS DRAWER                                   --}}
{{-- ====================================================== --}}

<div
    id="portalAccessDrawer"
    class="stitch-overlay fixed inset-0 z-[10000]">

    <div
        class="fixed inset-0 bg-[#213145]/30 backdrop-blur-sm"
        onclick="closeStitchModal('portalAccessDrawer')">
    </div>

    <div class="fixed inset-y-0 right-0 max-w-md w-full bg-white shadow-2xl flex flex-col z-10">

        <div class="px-6 py-4 bg-surface-container-low flex justify-between items-start">

            <div>
                <h3
                    id="portalMemberName"
                    class="font-headline text-[20px] font-bold">
                    Portal Access
                </h3>

                <p
                    id="portalMemberCode"
                    class="font-code text-[11px] text-primary mt-1">
                </p>
            </div>

            <button
                type="button"
                onclick="closeStitchModal('portalAccessDrawer')">

                <span class="material-symbols-outlined">
                    close
                </span>
            </button>

        </div>

        <div class="flex-1 overflow-y-auto p-6">

            <div id="portalNoAccount" class="hidden">

                <div class="p-4 rounded-xl bg-surface-container-low">

                    <div class="font-semibold">
                        No portal account
                    </div>

                    <p class="text-[12px] text-on-surface-variant mt-1">
                        This member does not have login access yet.
                    </p>

                </div>

                <button
                    type="button"
                    id="portalCreateForMember"
                    class="w-full mt-4 px-4 py-3 rounded-xl bg-primary text-white text-[13px] font-semibold">
                    Create Portal Account
                </button>

            </div>

            <div id="portalExistingAccount">

                <div class="space-y-3">

                    <div class="p-3 rounded-xl bg-surface-container-low">
                        <div class="text-[10px] text-on-surface-variant uppercase">
                            Username
                        </div>

                        <div
                            id="portalUsername"
                            class="font-code text-[13px] font-semibold mt-1">
                        </div>
                    </div>

                    <div class="p-3 rounded-xl bg-surface-container-low">
                        <div class="text-[10px] text-on-surface-variant uppercase">
                            Email
                        </div>

                        <div
                            id="portalEmail"
                            class="text-[13px] font-medium mt-1">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">

                        <div class="p-3 rounded-xl bg-surface-container-low">

                            <div class="text-[10px] text-on-surface-variant uppercase">
                                Portal
                            </div>

                            <div
                                id="portalStatus"
                                class="font-semibold text-[13px] mt-1">
                            </div>
                        </div>

                        <div class="p-3 rounded-xl bg-surface-container-low">

                            <div class="text-[10px] text-on-surface-variant uppercase">
                                Login Status
                            </div>

                            <div
                                id="portalLastLogin"
                                class="text-[12px] mt-1">
                            </div>
                        </div>

                    </div>

                    <div class="p-3 rounded-xl bg-surface-container-low">

                        <div class="text-[10px] text-on-surface-variant uppercase">
                            Mobile Verification
                        </div>

                        <div
                            id="portalMobileVerified"
                            class="text-[12px] font-medium mt-1">
                        </div>
                    </div>

                </div>

                <div class="mt-6">

                    <div class="text-[11px] uppercase tracking-wider font-semibold text-on-surface-variant mb-2">
                        Access Controls
                    </div>

                    <div class="grid grid-cols-2 gap-2">

                        <form
                            method="POST"
                            action=""
                            id="portalActivateForm">
                            @csrf

                            <button
                                type="submit"
                                class="w-full px-3 py-2.5 rounded-xl bg-secondary-container text-on-secondary-container text-[12px] font-semibold">

                                Activate
                            </button>
                        </form>

                        <form
                            method="POST"
                            action=""
                            id="portalDeactivateForm">
                            @csrf

                            <button
                                type="submit"
                                class="w-full px-3 py-2.5 rounded-xl bg-surface-container-high text-[12px] font-semibold">

                                Deactivate
                            </button>
                        </form>

                        <form
                            method="POST"
                            action=""
                            id="portalResetForm">
                            @csrf

                            <button
                                type="submit"
                                onclick="return confirm('Reset portal access and issue a new temporary password?')"
                                class="w-full px-3 py-2.5 rounded-xl bg-error-container text-error text-[12px] font-semibold">

                                Reset Access
                            </button>
                        </form>

                        <form
                            method="POST"
                            action=""
                            id="portalUnlockOtpForm">
                            @csrf

                            <button
                                type="submit"
                                class="w-full px-3 py-2.5 rounded-xl bg-surface-container-low text-[12px] font-semibold">

                                Unlock OTP
                            </button>
                        </form>

                    </div>

                    <form
                        method="POST"
                        action=""
                        id="portalVerifyMobileForm"
                        class="mt-2">
                        @csrf

                        <button
                            type="submit"
                            id="portalVerifyMobileButton"
                            class="w-full px-3 py-2.5 rounded-xl bg-primary text-white text-[12px] font-semibold">

                            Mark Mobile Verified
                        </button>
                    </form>

                </div>

            </div>

        </div>

    </div>
</div>

@endif




</div>

@push('scripts')
<script>
function openStitchModal(id) {
    const el = document.getElementById(id);

    if (!el) return;

    el.classList.add('is-open');
    document.body.classList.add('modal-open');
}

function closeStitchModal(id) {
    const el = document.getElementById(id);

    if (!el) return;

    el.classList.remove('is-open');
    document.body.classList.remove('modal-open');
}

function closeMemberMenus() {
    document
        .querySelectorAll('.member-action-menu.is-open')
        .forEach(function(menu) {
            menu.classList.remove('is-open');
        });
}

function toggleMemberMenu(id, button, event) {
    event.stopPropagation();

    const menu = document.getElementById(id);

    if (!menu) return;

    const opening =
        !menu.classList.contains('is-open');

    closeMemberMenus();

    if (!opening) return;

    const rect =
        button.getBoundingClientRect();

    const width = 220;

    let left =
        rect.right - width;

    if (left < 8) {
        left = 8;
    }

    let top =
        rect.bottom + 5;

    menu.style.left =
        left + 'px';

    menu.style.top =
        top + 'px';

    menu.classList.add('is-open');
}


function openCreatePortalAccount(memberId = '') {
    const select =
        document.getElementById('portalCreateMember');

    if (select) {
        select.value = memberId || '';
    }

    openStitchModal('createPortalModal');
}

function openPortalAccess(button) {
    closeMemberMenus();

    try {
        const member =
            JSON.parse(
                atob(button.dataset.portal)
            );

        document.getElementById(
            'portalMemberName'
        ).textContent =
            member.name || 'Portal Access';

        document.getElementById(
            'portalMemberCode'
        ).textContent =
            member.code || '';

        const noAccount =
            document.getElementById(
                'portalNoAccount'
            );

        const existing =
            document.getElementById(
                'portalExistingAccount'
            );

        if (!member.user_id) {
            noAccount.classList.remove(
                'hidden'
            );

            existing.classList.add(
                'hidden'
            );

            const createButton =
                document.getElementById(
                    'portalCreateForMember'
                );

            createButton.onclick =
                function() {
                    closeStitchModal(
                        'portalAccessDrawer'
                    );

                    openCreatePortalAccount(
                        member.id
                    );
                };
        } else {
            noAccount.classList.add(
                'hidden'
            );

            existing.classList.remove(
                'hidden'
            );

            document.getElementById(
                'portalUsername'
            ).textContent =
                member.username || '-';

            document.getElementById(
                'portalEmail'
            ).textContent =
                member.email || '-';

            document.getElementById(
                'portalStatus'
            ).textContent =
                member.portal_enabled
                    ? 'Enabled'
                    : 'Disabled';

            document.getElementById(
                'portalLastLogin'
            ).textContent =
                member.last_login
                    ? member.last_login
                    : 'Never logged in';

            document.getElementById(
                'portalMobileVerified'
            ).textContent =
                member.mobile_verified
                    ? member.mobile_verified
                    : 'Not verified';

            const base =
                @js(url('/admin/member-accounts'))
                + '/'
                + member.id;

            document.getElementById(
                'portalActivateForm'
            ).action =
                base + '/activate';

            document.getElementById(
                'portalDeactivateForm'
            ).action =
                base + '/deactivate';

            document.getElementById(
                'portalResetForm'
            ).action =
                base + '/reset';

            document.getElementById(
                'portalUnlockOtpForm'
            ).action =
                base + '/unlock-otp';

            document.getElementById(
                'portalVerifyMobileForm'
            ).action =
                base
                + '/mark-mobile-verified';

            const verifyButton =
                document.getElementById(
                    'portalVerifyMobileButton'
                );

            if (member.mobile_verified) {
                verifyButton.disabled = true;

                verifyButton.textContent =
                    'Mobile Already Verified';

                verifyButton.classList.add(
                    'opacity-50',
                    'cursor-not-allowed'
                );
            } else {
                verifyButton.disabled = false;

                verifyButton.textContent =
                    'Mark Mobile Verified';

                verifyButton.classList.remove(
                    'opacity-50',
                    'cursor-not-allowed'
                );
            }
        }

        openStitchModal(
            'portalAccessDrawer'
        );

    } catch (error) {
        console.error(error);

        alert(
            'Unable to open Portal Access.'
        );
    }
}

function openEditMember(button) {
    closeMemberMenus();

    try {
        const member =
            JSON.parse(
                atob(button.dataset.member)
            );

        document.getElementById('editMemberForm').action =
            @js(url('/admin/members'))
            + '/'
            + member.id;

        document.getElementById('editSubtitle').textContent =
            member.code
            + ' — '
            + member.name;

        document.getElementById('editCode').value =
            member.code || '';

        document.getElementById('editName').value =
            member.name || '';

        document.getElementById('editDepartment').value =
            member.department || '';

        document.getElementById('editMess').value =
            member.mess || '';

        document.getElementById('editMobile').value =
            member.mobile || '';

        document.getElementById('editJoin').value =
            member.join || '';

        document.getElementById('editLeave').value =
            member.leave || '';

        document.getElementById('editUser').value =
            member.user || '';

        document.getElementById('editActive').checked =
            !!member.active;

        openStitchModal('editDrawer');

    } catch (error) {
        console.error(error);

        alert(
            'Unable to open member record.'
        );
    }
}

function openRemoveMember(
    id,
    name,
    code,
    canDelete,
    message
) {
    closeMemberMenus();

    document.getElementById('removeForm').action =
        @js(url('/admin/members'))
        + '/'
        + id
        + '/remove';

    document.getElementById('removeTarget').textContent =
        name
        + ' ('
        + code
        + ')';

    document.getElementById('removeMessage').textContent =
        message;

    document.getElementById('removeTitle').textContent =
        canDelete
        ? 'Confirm Permanent Deletion'
        : 'Safe Deactivate Record';

    document.getElementById('removeSubmit').textContent =
        canDelete
        ? 'Delete Member Permanently'
        : 'Deactivate Member Only';

    openStitchModal('removeModal');
}

function openResetPassword(
    id,
    name,
    code
) {
    closeMemberMenus();

    const form =
        document.getElementById('resetForm');

    if (!form) return;

    form.reset();

    form.action =
        @js(url('/admin/members'))
        + '/'
        + id
        + '/reset-password';

    document.getElementById('resetTarget').textContent =
        name
        + ' ('
        + code
        + ')';

    openStitchModal('resetModal');
}

document.addEventListener(
    'click',
    function(event) {
        if (
            !event.target.closest(
                '.member-action-menu'
            )
        ) {
            closeMemberMenus();
        }
    }
);

document.addEventListener(
    'keydown',
    function(event) {
        if (event.key !== 'Escape') {
            return;
        }

        closeMemberMenus();

        document
            .querySelectorAll(
                '.stitch-overlay.is-open'
            )
            .forEach(function(modal) {
                modal.classList.remove(
                    'is-open'
                );
            });

        document.body.classList.remove(
            'modal-open'
        );
    }
);
</script>


@endpush

@endsection
