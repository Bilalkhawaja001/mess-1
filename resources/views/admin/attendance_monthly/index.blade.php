@extends('layouts.app')

@section('title', 'Monthly Attendance')
@section('page_title', 'Monthly Attendance')

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

.attendance-overlay {
    display: none;
}

.attendance-overlay.is-open {
    display: block;
}

.attendance-action-menu {
    display: none;
    position: fixed;
    z-index: 9999;
    width: 230px;
}

.attendance-action-menu.is-open {
    display: block;
}

body.modal-open {
    overflow: hidden;
}




/* Search-bar style outline for attendance summary cards */
.attendance-mess-summary {
    border: 1px solid #c7c4d7 !important;
}

</style>
@endpush


@section('content')

@php
    $cycle = \App\Support\BusinessMonthCycle::resolve($monthCycle);

    $monthLabel = \Carbon\Carbon::createFromFormat(
        'Y-m',
        $monthCycle
    )->format('F Y');

    $memberCount = $rows->count();

    $presentTotal = $rows->sum(
        fn($row) => (int) $row['present_days']
    );

    $lockedCount = $rows
        ->filter(fn($row) => (bool) $row['is_locked'])
        ->count();

    $editableCount = max(
        0,
        $memberCount - $lockedCount
    );

    $approvedCount = $rows
        ->filter(fn($row) => !empty($row['approved_at']))
        ->count();

    $currentCard = collect($monthCards)
        ->firstWhere('month_cycle', $monthCycle);

    $contractors = (int) data_get(
        $currentCard,
        'contractors',
        0
    );

    $executive = (int) data_get(
        $currentCard,
        'executive',
        0
    );

    $centralized = (int) data_get(
        $currentCard,
        'centralized',
        0
    );
@endphp


<div class="attendance-stitch-page">
<div class="max-w-[1600px] mx-auto px-6 py-6">


{{-- FLASH --}}

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

    <ul class="list-disc ml-5 mb-0">
        @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif



{{-- MONTH TABS + QUICK STATS --}}

<div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-6">

    <div class="flex items-center gap-2 bg-surface-container-low p-1 rounded-xl shadow-sm overflow-x-auto">

        @foreach($monthCards as $card)

            @php
                $selected =
                    $card['month_cycle'] === $monthCycle;

                $tabLabel =
                    \Carbon\Carbon::createFromFormat(
                        'Y-m',
                        $card['month_cycle']
                    )->format('M Y');

                $attendanceTotal =
                    (int) $card['contractors']
                    + (int) $card['executive']
                    + (int) $card['centralized'];
            @endphp

            <a
                href="{{ route('admin.attendance-monthly.index', [
                    'month_cycle' => $card['month_cycle']
                ]) }}"
                class="flex items-center gap-2 px-4 py-2 rounded-lg no-underline transition-all whitespace-nowrap
                {{ $selected
                    ? 'bg-white text-primary shadow-sm'
                    : 'text-on-surface-variant' }}">

                <span class="w-2 h-2 rounded-full
                    {{ $selected
                        ? 'bg-secondary'
                        : 'bg-outline' }}">
                </span>

                <span class="text-[13px] font-medium">
                    {{ $tabLabel }}
                </span>

                <span class="px-2 py-0.5 rounded-full font-code text-[11px] font-semibold
                    {{ $selected
                        ? 'bg-secondary-container text-on-secondary-container'
                        : 'bg-surface-container-high text-on-surface-variant' }}">

                    {{ number_format($attendanceTotal) }}
                </span>
            </a>

        @endforeach

    </div>


    <div class="flex flex-wrap items-center gap-4">

        <div class="flex items-center gap-3 px-4 py-2 bg-white rounded-xl shadow-sm">

            <div class="w-8 h-8 rounded-lg bg-surface-container-low flex items-center justify-center text-primary">

                <span class="material-symbols-outlined text-[18px]">
                    groups
                </span>

            </div>

            <div>
                <div class="text-[11px] text-on-surface-variant">
                    Active Members
                </div>

                <div class="font-headline text-[16px] font-bold">
                    {{ $memberCount }}
                </div>
            </div>

        </div>


        <div class="flex items-center gap-3 px-4 py-2 bg-white rounded-xl shadow-sm">

            <div class="w-8 h-8 rounded-lg bg-surface-container-low flex items-center justify-center text-secondary">

                <span class="material-symbols-outlined text-[18px]">
                    event_available
                </span>

            </div>

            <div>
                <div class="text-[11px] text-on-surface-variant">
                    Present Days
                </div>

                <div class="font-headline text-[16px] font-bold">
                    {{ number_format($presentTotal) }}
                </div>
            </div>

        </div>

    </div>

</div>



{{-- COMMAND BAR --}}

<div class="bg-white p-4 rounded-xl shadow-sm mb-6 flex flex-col xl:flex-row items-stretch xl:items-center justify-between gap-4">

    <div class="flex flex-col md:flex-row items-stretch md:items-center gap-2 flex-1">

        <div class="relative flex-1 max-w-xl">

            <input
                type="search"
                id="attendanceSearch"
                class="w-full bg-surface-container-low pl-4 pr-4 py-2 rounded-xl text-[13px] outline-none focus:bg-white focus:ring-2 focus:ring-primary"
                placeholder="Filter by Code, Name, or Department...">

        </div>


        <select
            id="attendanceStatus"
            class="bg-surface-container-low px-3 py-2 rounded-xl text-[13px] outline-none focus:ring-2 focus:ring-primary">

            <option value="all">
                All Status
            </option>

            <option value="editable">
                Editable
            </option>

            <option value="locked">
                Locked
            </option>

        </select>


        <form
            method="GET"
            action="{{ route('admin.attendance-monthly.index') }}"
            class="flex items-center gap-2">

            <input
                type="month"
                name="month_cycle"
                value="{{ $monthCycle }}"
                class="bg-surface-container-low px-3 py-2 rounded-xl text-[13px] outline-none focus:ring-2 focus:ring-primary"
                required>

            <button
                type="submit"
                class="px-4 py-2 bg-surface-container text-on-surface rounded-xl text-[13px] font-medium">

                Load
            </button>

        </form>

    </div>


    <div class="flex items-center gap-2 flex-wrap">

        <button
            type="button"
            onclick="openAttendanceOverlay('attendanceImportModal')"
            class="px-4 py-2 rounded-xl bg-surface-container-low text-on-surface hover:text-primary text-[13px] font-medium flex items-center gap-2">

            <span class="material-symbols-outlined text-[18px]">
                upload_file
            </span>

            Import CSV
        </button>


        <button
            type="button"
            onclick="openAttendanceOverlay('attendanceManualDrawer')"
            class="px-4 py-2 rounded-xl bg-primary text-white hover:bg-primary-container text-[13px] font-medium flex items-center gap-2">

            <span class="material-symbols-outlined text-[18px]">
                person_add
            </span>

            Manual Entry
        </button>


        <button
            type="button"
            onclick="toggleAttendanceMenu(this)"
            class="px-3 py-2 rounded-xl bg-surface-container-low text-on-surface-variant hover:text-primary">

            <span class="material-symbols-outlined text-[20px]">
                more_horiz
            </span>
        </button>

    </div>

</div>



{{-- MESS ATTENDANCE QUICK STATS --}}

<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">

    <div class="attendance-mess-summary flex items-center justify-between gap-3 px-4 py-3 bg-white rounded-xl shadow-sm">

        <div class="flex items-center gap-3">

            <div class="w-9 h-9 rounded-lg bg-primary-fixed flex items-center justify-center text-primary">

                <span class="material-symbols-outlined text-[19px]">
                    engineering
                </span>

            </div>

            <div>
                <div class="text-[11px] text-on-surface-variant">
                    Contractors
                </div>

                <div class="text-[10px] text-outline">
                    {{ $monthLabel }}
                </div>
            </div>

        </div>

        <div class="font-headline text-[18px] font-bold">
            {{ number_format($contractors) }}
        </div>

    </div>


    <div class="attendance-mess-summary flex items-center justify-between gap-3 px-4 py-3 bg-white rounded-xl shadow-sm">

        <div class="flex items-center gap-3">

            <div class="w-9 h-9 rounded-lg bg-surface-container-low flex items-center justify-center text-secondary">

                <span class="material-symbols-outlined text-[19px]">
                    business_center
                </span>

            </div>

            <div>
                <div class="text-[11px] text-on-surface-variant">
                    Executive
                </div>

                <div class="text-[10px] text-outline">
                    {{ $monthLabel }}
                </div>
            </div>

        </div>

        <div class="font-headline text-[18px] font-bold">
            {{ number_format($executive) }}
        </div>

    </div>


    <div class="attendance-mess-summary flex items-center justify-between gap-3 px-4 py-3 bg-white rounded-xl shadow-sm">

        <div class="flex items-center gap-3">

            <div class="w-9 h-9 rounded-lg bg-surface-container-high flex items-center justify-center text-primary">

                <span class="material-symbols-outlined text-[19px]">
                    restaurant
                </span>

            </div>

            <div>
                <div class="text-[11px] text-on-surface-variant">
                    Centralized
                </div>

                <div class="text-[10px] text-outline">
                    {{ $monthLabel }}
                </div>
            </div>

        </div>

        <div class="font-headline text-[18px] font-bold">
            {{ number_format($centralized) }}
        </div>

    </div>

</div>



{{-- TABLE CARD --}}

<div class="bg-white rounded-xl shadow-sm overflow-hidden">

    <div class="px-6 py-4 flex flex-col md:flex-row md:items-center justify-between gap-3">

        <div>

            <div class="font-headline text-[16px] font-bold text-on-surface">
                Monthly Attendance Register
            </div>

            <div class="text-[11px] text-on-surface-variant mt-1">
                {{ $cycle['cycle_start_date'] }}
                →
                {{ $cycle['cycle_end_date'] }}
                · {{ $cycle['cycle_days'] }} day business cycle
            </div>

        </div>


        <div class="flex items-center gap-2">

            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-secondary-container text-on-secondary-container text-[11px] font-semibold">

                <span class="w-1.5 h-1.5 rounded-full bg-secondary"></span>

                {{ $editableCount }} Editable
            </span>


            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full bg-surface-container text-outline text-[11px] font-semibold">

                <span class="material-symbols-outlined text-[14px]">
                    lock
                </span>

                {{ $lockedCount }} Locked
            </span>

        </div>

    </div>


    <form
        method="POST"
        action="{{ route('admin.attendance-monthly.store') }}">

        @csrf

        <input
            type="hidden"
            name="month_cycle"
            value="{{ $monthCycle }}">


        <div class="overflow-x-auto">

            <table class="w-full text-left">

                <thead>

                    <tr class="bg-surface-container-low text-on-surface-variant text-[10px] uppercase tracking-wider">

                        <th class="px-4 py-3.5 w-12">
                            #
                        </th>

                        <th class="px-4 py-3.5">
                            Member Details
                        </th>

                        <th class="px-4 py-3.5">
                            Department
                        </th>

                        <th class="px-4 py-3.5">
                            Present Days
                        </th>

                        <th class="px-4 py-3.5">
                            Status
                        </th>

                        <th class="px-4 py-3.5">
                            Approval
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-surface-container">

                @foreach($rows as $i => $r)

                    @php
                        $member = $r['member'];

                        $initials = collect(
                            preg_split(
                                '/\s+/',
                                trim($member->name)
                            )
                        )
                        ->filter()
                        ->take(2)
                        ->map(
                            fn($part) =>
                                mb_strtoupper(
                                    mb_substr($part, 0, 1)
                                )
                        )
                        ->implode('');

                        $searchText = strtolower(
                            $member->member_code
                            . ' '
                            . $member->name
                            . ' '
                            . ($member->department_name ?? '')
                        );

                        $approvedAt =
                            !empty($r['approved_at'])
                            ? \Illuminate\Support\Carbon::parse(
                                $r['approved_at']
                            )->format('d M Y, h:i A')
                            : null;
                    @endphp


                    <tr
                        class="attendance-row hover:bg-surface-container-lowest transition-colors"
                        data-search="{{ e($searchText) }}"
                        data-status="{{ $r['is_locked'] ? 'locked' : 'editable' }}">

                        <td class="px-4 py-3 text-[11px] text-outline">
                            {{ $i + 1 }}
                        </td>


                        <td class="px-4 py-3">

                            <div class="flex items-center gap-3">

                                <div class="w-9 h-9 rounded-full bg-primary-fixed flex items-center justify-center text-primary font-headline font-bold flex-shrink-0">

                                    {{ $initials ?: 'M' }}

                                </div>


                                <div class="min-w-0">

                                    <div class="font-headline text-[13px] font-semibold text-on-surface truncate">
                                        {{ $member->name }}
                                    </div>

                                    <div class="font-code text-[11px] text-primary font-medium">
                                        {{ $member->member_code }}
                                    </div>

                                </div>

                            </div>

                        </td>


                        <td class="px-4 py-3">

                            <div class="text-[12px] text-on-surface">
                                {{ $member->department_name ?: '—' }}
                            </div>

                        </td>


                        <td class="px-4 py-3">

                            @if(!$r['is_locked'])

                                <input
                                    type="hidden"
                                    name="rows[{{ $i }}][member_id]"
                                    value="{{ $member->id }}">

                                <div class="flex items-center gap-2">

                                    <input
                                        type="number"
                                        name="rows[{{ $i }}][present_days]"
                                        value="{{ old("rows.$i.present_days", $r['present_days']) }}"
                                        min="0"
                                        max="{{ $cycle['cycle_days'] }}"
                                        class="w-20 bg-surface-container-low px-3 py-2 rounded-lg outline-none focus:ring-2 focus:ring-primary font-code text-[12px]"
                                        required>

                                    <span class="font-code text-[10px] text-outline">
                                        / {{ $cycle['cycle_days'] }}
                                    </span>

                                </div>

                            @else

                                <div class="flex items-center gap-2">

                                    <span class="inline-flex min-w-16 justify-center px-3 py-2 rounded-lg bg-surface-container-low font-code text-[12px] text-on-surface-variant">

                                        {{ $r['present_days'] }}

                                    </span>

                                    <span class="font-code text-[10px] text-outline">
                                        / {{ $cycle['cycle_days'] }}
                                    </span>

                                </div>

                            @endif

                        </td>


                        <td class="px-4 py-3">

                            @if($r['is_locked'])

                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-surface-container text-outline text-[11px] font-semibold">

                                    <span class="material-symbols-outlined text-[13px]">
                                        lock
                                    </span>

                                    Locked
                                </span>

                            @else

                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-secondary-container text-on-secondary-container text-[11px] font-semibold">

                                    <span class="w-1.5 h-1.5 rounded-full bg-secondary"></span>

                                    Editable
                                </span>

                            @endif

                        </td>


                        <td class="px-4 py-3">

                            @if($approvedAt)

                                <div class="text-[11px]">
                                    <span class="text-secondary font-semibold">
                                        Approved
                                    </span>

                                    <div class="text-on-surface-variant mt-1">
                                        {{ $approvedAt }}
                                    </div>
                                </div>

                            @else

                                <span class="text-[11px] text-on-surface-variant">
                                    Not approved
                                </span>

                            @endif

                        </td>

                    </tr>

                @endforeach

                </tbody>

            </table>

        </div>


        <div class="px-6 py-4 bg-surface-container-low flex flex-col md:flex-row items-center justify-between gap-4 text-[12px] text-on-surface-variant">

            <div>

                Showing
                <span
                    id="attendanceVisibleCount"
                    class="font-code font-medium text-on-surface">

                    {{ $memberCount }}
                </span>

                members

                <span class="mx-1">·</span>

                {{ $approvedCount }} approved

            </div>


            @if($editableCount > 0)

                <div class="flex items-center gap-2">

                    <button
                        type="submit"
                        class="px-4 py-2 rounded-xl bg-surface-container text-on-surface text-[13px] font-medium border-0">

                        Save Changes
                    </button>


                    <button
                        type="submit"
                        name="approve"
                        value="1"
                        onclick="return confirm('Save and approve/lock current editable attendance for {{ $monthLabel }}?')"
                        class="px-5 py-2 rounded-xl bg-primary text-white text-[13px] font-medium border-0 flex items-center gap-2">

                        <span class="material-symbols-outlined text-[18px]">
                            verified
                        </span>

                        Save & Approve / Lock
                    </button>

                </div>

            @else

                <span class="inline-flex items-center gap-2 px-3 py-2 rounded-xl bg-surface-container">

                    <span class="material-symbols-outlined text-[16px]">
                        lock
                    </span>

                    All records are locked
                </span>

            @endif

        </div>

    </form>

</div>

</div>
</div>



{{-- MORE MENU --}}

<div
    id="attendanceActionMenu"
    class="attendance-action-menu rounded-xl bg-white shadow-xl py-1">

    <a
        href="{{ route('admin.attendance-monthly.export', [
            'month_cycle' => $monthCycle
        ]) }}"
        class="w-full no-underline text-left px-4 py-2 text-[12px] hover:bg-surface-container-low text-on-surface flex items-center gap-2">

        <span class="material-symbols-outlined text-[17px]">
            download
        </span>

        Export Attendance
    </a>


    <a
        href="{{ route('admin.attendance-monthly.template') }}"
        class="w-full no-underline text-left px-4 py-2 text-[12px] hover:bg-surface-container-low text-on-surface flex items-center gap-2">

        <span class="material-symbols-outlined text-[17px]">
            description
        </span>

        Download CSV Template
    </a>


    <div class="h-px bg-surface-container my-1"></div>


    <form
        method="POST"
        action="{{ route('admin.attendance-monthly.approve') }}"
        onsubmit="return confirm('Approve and lock monthly attendance for {{ $monthLabel }}?')">

        @csrf

        <input
            type="hidden"
            name="month_cycle"
            value="{{ $monthCycle }}">

        <button
            type="submit"
            class="w-full text-left px-4 py-2 text-[12px] hover:bg-surface-container-low border-0 bg-transparent flex items-center gap-2">

            <span class="material-symbols-outlined text-[17px]">
                verified_user
            </span>

            Approve / Lock Month
        </button>

    </form>


    @if($lockedCount > 0)

        <form
            method="POST"
            action="{{ route('admin.attendance-monthly.unlock') }}"
            onsubmit="return confirm('Unlock monthly attendance for {{ $monthLabel }}?')">

            @csrf

            <input
                type="hidden"
                name="month_cycle"
                value="{{ $monthCycle }}">

            <button
                type="submit"
                class="w-full text-left px-4 py-2 text-[12px] hover:bg-surface-container-low border-0 bg-transparent flex items-center gap-2">

                <span class="material-symbols-outlined text-[17px]">
                    lock_open
                </span>

                Unlock Month
            </button>

        </form>

    @endif

</div>



{{-- MANUAL ENTRY DRAWER --}}

<div
    id="attendanceManualDrawer"
    class="attendance-overlay fixed inset-0 z-[9998]">

    <div
        class="fixed inset-0 bg-slate-900/30 backdrop-blur-sm"
        onclick="closeAttendanceOverlay('attendanceManualDrawer')">
    </div>


    <div class="fixed inset-y-0 right-0 max-w-lg w-full bg-white shadow-2xl flex flex-col z-10">

        <div class="px-6 py-4 bg-surface-container-low flex items-center justify-between">

            <div>
                <div class="font-headline text-[18px] font-bold">
                    Manual Attendance Entry
                </div>

                <div class="text-[11px] text-on-surface-variant mt-1">
                    Add or update one member for {{ $monthLabel }}
                </div>
            </div>


            <button
                type="button"
                onclick="closeAttendanceOverlay('attendanceManualDrawer')"
                class="p-1.5 rounded-lg text-on-surface-variant hover:bg-surface-container border-0 bg-transparent">

                <span class="material-symbols-outlined text-[20px]">
                    close
                </span>
            </button>

        </div>


        <form
            method="POST"
            action="{{ route('admin.attendance-monthly.manual') }}"
            class="flex-1 overflow-y-auto p-6 flex flex-col gap-4">

            @csrf

            <input
                type="hidden"
                name="month_cycle"
                value="{{ $monthCycle }}">


            <div>

                <label class="block text-[12px] font-medium text-on-surface mb-1">
                    Month Cycle
                </label>

                <input
                    value="{{ $monthLabel }}"
                    readonly
                    class="w-full bg-surface-container-low px-3 py-2 rounded-lg outline-none text-[13px]">

                <div class="text-[10px] text-on-surface-variant mt-1">
                    {{ $cycle['cycle_start_date'] }}
                    →
                    {{ $cycle['cycle_end_date'] }}
                </div>

            </div>


            <div>

                <label class="block text-[12px] font-medium text-on-surface mb-1">
                    Search Member
                </label>

                <div class="relative">

                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-outline text-[18px]">
                        search
                    </span>

                    <input
                        id="manualMemberSearch"
                        type="search"
                        class="w-full bg-surface-container-low pl-10 pr-3 py-2 rounded-lg outline-none focus:ring-2 focus:ring-primary text-[13px]"
                        placeholder="Code, name or department...">

                </div>

            </div>


            <div>

                <label class="block text-[12px] font-medium text-on-surface mb-1">
                    Member
                </label>

                <select
                    id="manualMemberSelect"
                    name="member_id"
                    class="w-full bg-surface-container-low px-3 py-2 rounded-lg outline-none focus:ring-2 focus:ring-primary text-[13px] border-0"
                    required>

                    <option value="">
                        Select member
                    </option>

                    @foreach($rows as $r)

                        @php
                            $manualMember = $r['member'];

                            $manualSearch = strtolower(
                                $manualMember->member_code
                                . ' '
                                . $manualMember->name
                                . ' '
                                . ($manualMember->department_name ?? '')
                            );
                        @endphp

                        <option
                            value="{{ $manualMember->id }}"
                            data-search="{{ e($manualSearch) }}">

                            {{ $manualMember->member_code }}
                            —
                            {{ $manualMember->name }}
                            —
                            {{ $manualMember->department_name }}

                        </option>

                    @endforeach

                </select>

            </div>


            <div>

                <label class="block text-[12px] font-medium text-on-surface mb-1">
                    Present Days
                </label>

                <input
                    type="number"
                    name="present_days"
                    min="0"
                    max="{{ $cycle['cycle_days'] }}"
                    class="w-full bg-surface-container-low px-3 py-2 rounded-lg outline-none focus:ring-2 focus:ring-primary font-code text-[13px]"
                    required>

                <div class="text-[10px] text-on-surface-variant mt-1">
                    Maximum {{ $cycle['cycle_days'] }} days.
                </div>

            </div>


            <div class="mt-auto pt-4 flex justify-end gap-2">

                <button
                    type="button"
                    onclick="closeAttendanceOverlay('attendanceManualDrawer')"
                    class="px-4 py-2 rounded-xl bg-surface-container-low text-[13px] border-0">

                    Cancel
                </button>

                <button
                    type="submit"
                    class="px-5 py-2 rounded-xl bg-primary text-white text-[13px] flex items-center gap-2 border-0">

                    <span class="material-symbols-outlined text-[18px]">
                        check
                    </span>

                    Save Attendance
                </button>

            </div>

        </form>

    </div>

</div>



{{-- IMPORT MODAL --}}

<div
    id="attendanceImportModal"
    class="attendance-overlay fixed inset-0 z-[9998]">

    <div
        class="fixed inset-0 bg-slate-900/30 backdrop-blur-sm"
        onclick="closeAttendanceOverlay('attendanceImportModal')">
    </div>


    <div class="fixed inset-0 flex items-center justify-center p-4 z-10 pointer-events-none">

        <div class="w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden pointer-events-auto">

            <div class="px-6 py-4 bg-surface-container-low flex items-center justify-between">

                <div class="flex items-center gap-3">

                    <span class="material-symbols-outlined text-primary">
                        upload_file
                    </span>

                    <div>
                        <div class="font-headline text-[17px] font-bold">
                            Import Monthly Attendance
                        </div>

                        <div class="text-[11px] text-on-surface-variant">
                            Bulk CSV upload
                        </div>
                    </div>

                </div>


                <button
                    type="button"
                    onclick="closeAttendanceOverlay('attendanceImportModal')"
                    class="p-1.5 rounded-lg hover:bg-surface-container border-0 bg-transparent">

                    <span class="material-symbols-outlined">
                        close
                    </span>

                </button>

            </div>


            <form
                method="POST"
                action="{{ route('admin.attendance-monthly.import') }}"
                enctype="multipart/form-data">

                @csrf


                <div class="p-6">

                    <div class="flex items-center justify-between p-3 rounded-xl bg-surface-container-low mb-4">

                        <div>
                            <div class="text-[11px] font-semibold">
                                Required CSV columns
                            </div>

                            <div class="font-code text-[10px] text-secondary mt-1">
                                month_cycle,member_code,present_days
                            </div>
                        </div>


                        <a
                            href="{{ route('admin.attendance-monthly.template') }}"
                            class="px-3 py-1 rounded-lg bg-surface-container text-primary text-[11px] font-semibold no-underline">

                            Template
                        </a>

                    </div>


                    <div class="relative p-8 rounded-xl bg-surface-container-low text-center">

                        <span class="material-symbols-outlined text-[36px] text-primary">
                            cloud_upload
                        </span>

                        <div class="text-[13px] font-medium mt-2">
                            Select attendance CSV
                        </div>

                        <div class="text-[11px] text-on-surface-variant mb-3">
                            CSV or TXT · max 2 MB
                        </div>

                        <input
                            type="file"
                            name="csv_file"
                            accept=".csv,.txt,text/csv"
                            class="text-[12px]"
                            required>

                    </div>

                </div>


                <div class="px-6 py-4 bg-surface-container-low flex justify-end gap-2">

                    <button
                        type="button"
                        onclick="closeAttendanceOverlay('attendanceImportModal')"
                        class="px-4 py-2 rounded-xl bg-white text-[13px] border-0">

                        Cancel
                    </button>

                    <button
                        type="submit"
                        class="px-5 py-2 rounded-xl bg-primary text-white text-[13px] border-0">

                        Import Attendance
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>



@push('scripts')
<script>

function openAttendanceOverlay(id) {
    const modal = document.getElementById(id);

    if (!modal) {
        return;
    }

    modal.classList.add('is-open');
    document.body.classList.add('modal-open');
}


function closeAttendanceOverlay(id) {
    const modal = document.getElementById(id);

    if (!modal) {
        return;
    }

    modal.classList.remove('is-open');

    if (
        !document.querySelector(
            '.attendance-overlay.is-open'
        )
    ) {
        document.body.classList.remove(
            'modal-open'
        );
    }
}


function closeAttendanceMenu() {
    document
        .getElementById('attendanceActionMenu')
        ?.classList.remove('is-open');
}


function toggleAttendanceMenu(button) {

    const menu =
        document.getElementById(
            'attendanceActionMenu'
        );

    if (!menu) {
        return;
    }

    const isOpen =
        menu.classList.contains('is-open');

    closeAttendanceMenu();

    if (isOpen) {
        return;
    }

    const rect =
        button.getBoundingClientRect();

    const width = 230;

    menu.style.top =
        (rect.bottom + 6)
        + 'px';

    menu.style.left =
        Math.max(
            8,
            rect.right - width
        )
        + 'px';

    menu.classList.add('is-open');
}


document.addEventListener(
    'DOMContentLoaded',
    function () {

        const search =
            document.getElementById(
                'attendanceSearch'
            );

        const status =
            document.getElementById(
                'attendanceStatus'
            );

        const rows =
            Array.from(
                document.querySelectorAll(
                    '.attendance-row'
                )
            );

        const visible =
            document.getElementById(
                'attendanceVisibleCount'
            );


        function filterAttendanceRows() {

            const q =
                (search?.value || '')
                .trim()
                .toLowerCase();

            const state =
                status?.value || 'all';

            let count = 0;


            rows.forEach(
                function(row) {

                    const matchesSearch =
                        !q
                        ||
                        (
                            row.dataset.search
                            || ''
                        ).includes(q);

                    const matchesStatus =
                        state === 'all'
                        ||
                        row.dataset.status
                        === state;

                    const show =
                        matchesSearch
                        &&
                        matchesStatus;

                    row.style.display =
                        show
                        ? ''
                        : 'none';

                    if (show) {
                        count++;
                    }
                }
            );


            if (visible) {
                visible.textContent =
                    count;
            }
        }


        search?.addEventListener(
            'input',
            filterAttendanceRows
        );

        status?.addEventListener(
            'change',
            filterAttendanceRows
        );


        const memberSearch =
            document.getElementById(
                'manualMemberSearch'
            );

        const memberSelect =
            document.getElementById(
                'manualMemberSelect'
            );


        memberSearch?.addEventListener(
            'input',
            function () {

                const q =
                    this.value
                    .trim()
                    .toLowerCase();


                Array.from(
                    memberSelect.options
                )
                .forEach(
                    function(option, index) {

                        if (index === 0) {
                            option.hidden =
                                false;

                            return;
                        }

                        const text =
                            option.dataset.search
                            ||
                            option.textContent
                                .toLowerCase();

                        option.hidden =
                            q !== ''
                            &&
                            !text.includes(q);
                    }
                );
            }
        );

    }
);


document.addEventListener(
    'click',
    function(event) {

        if (
            !event.target.closest(
                '#attendanceActionMenu'
            )
            &&
            !event.target.closest(
                '[onclick^="toggleAttendanceMenu"]'
            )
        ) {
            closeAttendanceMenu();
        }
    }
);


document.addEventListener(
    'keydown',
    function(event) {

        if (event.key !== 'Escape') {
            return;
        }

        closeAttendanceMenu();

        document
            .querySelectorAll(
                '.attendance-overlay.is-open'
            )
            .forEach(
                function(modal) {
                    modal.classList.remove(
                        'is-open'
                    );
                }
            );

        document.body.classList.remove(
            'modal-open'
        );
    }
);

</script>
@endpush

@endsection
