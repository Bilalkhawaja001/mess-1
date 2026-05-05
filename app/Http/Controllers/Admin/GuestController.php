<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\DepartmentLedger;
use App\Models\Guest;
use App\Models\GuestMeal;
use App\Models\Member;
use App\Models\RatePolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GuestController extends Controller
{
    private const MEAL_TYPES = ['BREAKFAST', 'LUNCH', 'DINNER'];

    public function guestRate(Request $request): JsonResponse
    {
        $dateRaw = trim((string) $request->query('date', ''));
        if ($dateRaw === '') {
            return response()->json(['ok' => false, 'error' => 'date is required'], 400);
        }

        try {
            $date = Carbon::parse($dateRaw)->toDateString();
            $policy = $this->guestRatePolicyForDate($date);

            return response()->json([
                'ok' => true,
                'rate' => (float) $policy->value,
                'effective_from' => optional($policy->effective_from)->format('Y-m-d'),
                'effective_to' => optional($policy->effective_to)->format('Y-m-d'),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 400);
        }
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $fromDate = trim((string) $request->query('from_date', ''));
        $toDate = trim((string) $request->query('to_date', ''));

        $guestsQuery = Guest::query()
            ->with(['department', 'hostMember'])
            ->where('is_deleted', false)
            ->orderByDesc('id');

        if ($q !== '') {
            $guestsQuery->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('guest_code', 'like', "%{$q}%")
                    ->orWhere('came_from', 'like', "%{$q}%")
                    ->orWhere('remarks', 'like', "%{$q}%");
            });
        }

        $guests = $guestsQuery->get();

        $mealsQuery = GuestMeal::query()
            ->with(['guest.department', 'postedBy', 'approvedBy'])
            ->orderByDesc('meal_date')
            ->orderByDesc('id');

        if ($fromDate !== '') {
            $mealsQuery->whereDate('meal_date', '>=', $fromDate);
        }
        if ($toDate !== '') {
            $mealsQuery->whereDate('meal_date', '<=', $toDate);
        }

        $meals = $mealsQuery->limit(200)->get()->map(function (GuestMeal $meal) {
            [$rate, $amount, $rateMissing, $rateError] = $this->dynamicRatePayload($meal);
            $meal->rate_dynamic = $rate;
            $meal->amount_dynamic = $amount;
            $meal->rate_missing = $rateMissing;
            $meal->rate_error = $rateError;

            return $meal;
        });

        $summary = round((float) $meals->sum(fn (GuestMeal $meal) => (float) ($meal->amount_dynamic ?? $meal->amount ?? 0)), 2);

        $today = now()->toDateString();
        $currentRate = null;
        try {
            $currentRate = $this->guestRateForDate($today);
        } catch (\Throwable $e) {
            $currentRate = null;
        }

        $departments = Department::query()->orderBy('code')->get();
        $members = Member::query()->orderBy('member_code')->get(['id', 'member_code', 'name']);

        return view('admin.guests.index', [
            'guests' => $guests,
            'meals' => $meals,
            'summary' => $summary,
            'departments' => $departments,
            'members' => $members,
            'mealTypes' => self::MEAL_TYPES,
            'q' => $q,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'today' => $today,
            'currentRate' => $currentRate,
        ]);
    }

    public function storeGuest(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'guest_code' => 'nullable|string|max:50',
            'date' => 'nullable|date',
            'name' => 'required|string|max:255',
            'came_from' => 'nullable|string|max:120',
            'remarks' => 'nullable|string',
            'department_id' => 'required|exists:departments,id',
            'host_member_id' => 'nullable|exists:members,id',
        ]);

        $data['guest_code'] = trim((string) ($data['guest_code'] ?? '')) ?: $this->nextGuestCode();
        $data['is_active'] = true;
        $data['is_deleted'] = false;

        Guest::query()->create($data);

        return back()->with('success', 'Guest saved.');
    }

    public function updateGuest(Request $request, Guest $guest): RedirectResponse
    {
        $data = $request->validate([
            'date' => 'nullable|date',
            'name' => 'required|string|max:255',
            'came_from' => 'nullable|string|max:120',
            'remarks' => 'nullable|string',
            'department_id' => 'required|exists:departments,id',
            'host_member_id' => 'nullable|exists:members,id',
            'is_active' => 'nullable|boolean',
        ]);

        $data['is_active'] = (bool) ($data['is_active'] ?? false);
        $guest->update($data);

        return back()->with('success', 'Guest updated.');
    }

    public function deleteGuest(Guest $guest): RedirectResponse
    {
        if ($guest->meals()->exists()) {
            return back()->with('error', 'Guest has meal records; cannot delete.');
        }

        $guest->update([
            'is_active' => false,
            'is_deleted' => true,
        ]);

        return back()->with('success', 'Guest soft deleted.');
    }

    public function storeMeal(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'guest_id' => 'required|exists:guests,id',
            'meal_date' => 'required|date',
            'meal_type' => 'required|string|max:30',
            'quantity' => 'required|integer|min:1',
        ]);

        $mealType = strtoupper(trim((string) $data['meal_type']));
        if (! in_array($mealType, self::MEAL_TYPES, true)) {
            return back()->with('error', 'Invalid meal type.');
        }

        GuestMeal::query()->create([
            'guest_id' => $data['guest_id'],
            'meal_date' => $data['meal_date'],
            'meal_type' => $mealType,
            'quantity' => $data['quantity'],
            'rate' => 0,
            'rate_applied' => 0,
            'amount' => 0,
            'posted_by' => auth()->id(),
            'approved_by' => null,
            'posted_at' => now(),
            'approved_at' => null,
        ]);

        return back()->with('success', 'Guest meal draft saved.');
    }

    public function updateMeal(Request $request, GuestMeal $meal): RedirectResponse
    {
        $data = $request->validate([
            'guest_id' => 'required|exists:guests,id',
            'meal_date' => 'required|date',
            'meal_type' => 'required|string|max:30',
            'quantity' => 'required|integer|min:1',
        ]);

        $mealType = strtoupper(trim((string) $data['meal_type']));
        if (! in_array($mealType, self::MEAL_TYPES, true)) {
            return back()->with('error', 'Invalid meal type.');
        }

        $wasApproved = (bool) $meal->approved_at;
        $oldGuest = $meal->guest()->first();
        $oldDepartmentId = $oldGuest?->department_id;
        $oldMealDate = $meal->meal_date;
        $oldAmount = (float) ($meal->amount ?? 0);

        $rate = $this->guestRateForDate($data['meal_date']);
        $amount = round($rate * (int) $data['quantity'], 2);

        DB::transaction(function () use ($meal, $data, $mealType, $rate, $amount, $wasApproved, $oldDepartmentId, $oldMealDate, $oldAmount) {
            $meal->update([
                'guest_id' => $data['guest_id'],
                'meal_date' => $data['meal_date'],
                'meal_type' => $mealType,
                'quantity' => $data['quantity'],
                'rate' => $rate,
                'rate_applied' => $rate,
                'amount' => $amount,
            ]);

            if ($wasApproved) {
                if ($oldDepartmentId && $oldAmount > 0) {
                    $this->appendDepartmentLedgerEntry(
                        departmentId: $oldDepartmentId,
                        mealDate: $oldMealDate,
                        entryType: 'CREDIT',
                        amount: $oldAmount,
                        referenceId: $meal->id,
                        remarks: 'Guest meal approved edit reversal'
                    );
                }

                $this->appendDepartmentLedgerEntryForMeal(
                    meal: $meal->fresh(['guest']),
                    entryType: 'DEBIT',
                    remarks: 'Guest meal approved edit repost'
                );
            }
        });

        return back()->with('success', 'Guest meal updated.');
    }

    public function deleteMeal(GuestMeal $meal): RedirectResponse
    {
        $wasApproved = (bool) $meal->approved_at;
        $oldGuest = $meal->guest()->first();
        $oldDepartmentId = $oldGuest?->department_id;
        $oldMealDate = $meal->meal_date;
        $oldAmount = (float) ($meal->amount ?? 0);

        DB::transaction(function () use ($meal, $wasApproved, $oldDepartmentId, $oldMealDate, $oldAmount) {
            if ($wasApproved && $oldDepartmentId && $oldAmount > 0) {
                $this->appendDepartmentLedgerEntry(
                    departmentId: $oldDepartmentId,
                    mealDate: $oldMealDate,
                    entryType: 'CREDIT',
                    amount: $oldAmount,
                    referenceId: $meal->id,
                    remarks: 'Guest meal approved delete reversal'
                );
            }

            $meal->delete();
        });

        return back()->with('success', 'Guest meal deleted.');
    }

    public function approveMeal(GuestMeal $meal): RedirectResponse
    {
        if ($meal->approved_at) {
            return back()->with('error', 'Approved record is immutable.');
        }

        $rate = $this->guestRateForDate($meal->meal_date);
        $amount = round($rate * (int) $meal->quantity, 2);

        DB::transaction(function () use ($meal, $rate, $amount) {
            $meal->update([
                'rate' => $rate,
                'rate_applied' => $rate,
                'amount' => $amount,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
            ]);

            $this->appendDepartmentLedgerEntryForMeal(
                meal: $meal->fresh(['guest']),
                entryType: 'DEBIT',
                remarks: 'Guest meal chargeback approval'
            );
        });

        return back()->with('success', 'Guest meal approved. Department ledger updated.');
    }

    public function exportMeals(Request $request): StreamedResponse
    {
        $from = trim((string) $request->query('from', ''));
        $to = trim((string) $request->query('to', ''));

        $query = GuestMeal::query()
            ->with(['guest.department'])
            ->orderBy('meal_date')
            ->orderBy('id');

        if ($from !== '') {
            $query->whereDate('meal_date', '>=', $from);
        }
        if ($to !== '') {
            $query->whereDate('meal_date', '<=', $to);
        }

        $rows = $query->get();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['date', 'guest_id', 'guest_name', 'company / came_from', 'department', 'meal_type', 'qty', 'rate', 'total_amount', 'remarks', 'rate_missing']);
            foreach ($rows as $row) {
                [$rate, $amount, $rateMissing] = $this->dynamicRatePayload($row);
                fputcsv($out, [
                    optional($row->meal_date)->format('Y-m-d'),
                    $row->guest?->id,
                    $row->guest?->name,
                    $row->guest?->came_from,
                    $row->guest?->department?->code,
                    $row->meal_type,
                    $row->quantity,
                    $rate,
                    $amount,
                    $row->guest?->remarks,
                    $rateMissing ? 'YES' : 'NO',
                ]);
            }
            fclose($out);
        }, 'guest_meals_export.csv', ['Content-Type' => 'text/csv']);
    }

    public function importGuests(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt']);
        $counts = ['inserted' => 0, 'updated' => 0, 'failed' => 0];

        foreach ($this->csvRows($request) as $row) {
            $departmentId = $this->resolveDepartmentId($row);
            $hostMemberId = $this->resolveMemberId($row);

            $payload = [
                'guest_code' => $this->nullableText($row['guest_code'] ?? null),
                'date' => $this->nullableText($row['date'] ?? null),
                'name' => trim((string) ($row['name'] ?? '')),
                'came_from' => $this->nullableText($row['came_from'] ?? ($row['company'] ?? null)),
                'remarks' => $this->nullableText($row['remarks'] ?? null),
                'department_id' => $departmentId,
                'host_member_id' => $hostMemberId,
                'is_active' => $this->toBoolean($row['is_active'] ?? true),
                'is_deleted' => $this->toBoolean($row['is_deleted'] ?? false),
            ];

            $validator = Validator::make($payload, [
                'guest_code' => 'nullable|string|max:50',
                'date' => 'nullable|date',
                'name' => 'required|string|max:255',
                'came_from' => 'nullable|string|max:120',
                'remarks' => 'nullable|string',
                'department_id' => 'required|exists:departments,id',
                'host_member_id' => 'nullable|exists:members,id',
                'is_active' => 'boolean',
                'is_deleted' => 'boolean',
            ]);

            if ($validator->fails()) {
                $counts['failed']++;
                continue;
            }

            $payload['guest_code'] = $payload['guest_code'] ?: $this->nextGuestCode();

            $existing = null;
            if ($payload['guest_code']) {
                $existing = Guest::query()->where('guest_code', $payload['guest_code'])->first();
            }
            if (! $existing) {
                $existing = Guest::query()
                    ->where('name', $payload['name'])
                    ->where('department_id', $payload['department_id'])
                    ->whereDate('date', $payload['date'])
                    ->first();
            }

            if ($existing) {
                $existing->update($payload);
                $counts['updated']++;
            } else {
                Guest::query()->create($payload);
                $counts['inserted']++;
            }
        }

        return back()->with('success', "Guests import done. Inserted: {$counts['inserted']}, Updated: {$counts['updated']}, Failed: {$counts['failed']}");
    }

    public function importMeals(Request $request): RedirectResponse
    {
        $request->validate(['file' => 'required|file|mimes:csv,txt']);
        $counts = ['inserted' => 0, 'updated' => 0, 'failed' => 0];

        foreach ($this->csvRows($request) as $row) {
            $guestId = $this->resolveGuestId($row);
            $mealDate = $this->nullableText($row['meal_date'] ?? ($row['date'] ?? null));
            $mealType = strtoupper(trim((string) ($row['meal_type'] ?? '')));
            $quantity = $row['quantity'] ?? ($row['qty'] ?? null);

            $payload = [
                'guest_id' => $guestId,
                'meal_date' => $mealDate,
                'meal_type' => $mealType,
                'quantity' => $quantity,
            ];

            $validator = Validator::make($payload, [
                'guest_id' => 'required|exists:guests,id',
                'meal_date' => 'required|date',
                'meal_type' => 'required|string|in:BREAKFAST,LUNCH,DINNER',
                'quantity' => 'required|integer|min:1',
            ]);

            if ($validator->fails()) {
                $counts['failed']++;
                continue;
            }

            try {
                $rate = $this->guestRateForDate($payload['meal_date']);
            } catch (\Throwable $e) {
                $counts['failed']++;
                continue;
            }

            $amount = round($rate * (int) $payload['quantity'], 2);
            $attributes = [
                'guest_id' => $payload['guest_id'],
                'meal_date' => $payload['meal_date'],
                'meal_type' => $payload['meal_type'],
            ];
            $values = [
                'quantity' => $payload['quantity'],
                'rate' => $rate,
                'rate_applied' => $rate,
                'amount' => $amount,
                'posted_by' => auth()->id(),
                'posted_at' => now(),
            ];

            $existing = GuestMeal::query()->where($attributes)->first();
            if ($existing) {
                $existing->update($values);
                $counts['updated']++;
            } else {
                GuestMeal::query()->create($attributes + $values + ['approved_by' => null, 'approved_at' => null]);
                $counts['inserted']++;
            }
        }

        return back()->with('success', "Guest meals import done. Inserted: {$counts['inserted']}, Updated: {$counts['updated']}, Failed: {$counts['failed']}");
    }

    private function guestRatePolicyForDate(string $date): RatePolicy
    {
        $policy = RatePolicy::query()
            ->where('rate_type', 'GUEST')
            ->where('is_active', true)
            ->whereNotNull('approved_at')
            ->whereDate('effective_from', '<=', $date)
            ->where(function ($query) use ($date) {
                $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date);
            })
            ->orderByDesc('effective_from')
            ->first();

        if (! $policy) {
            throw new \RuntimeException('Rate not configured for this date/meal_type');
        }

        return $policy;
    }

    private function guestRateForDate(string $date): float
    {
        return (float) $this->guestRatePolicyForDate(Carbon::parse($date)->toDateString())->value;
    }

    private function nextGuestCode(): string
    {
        $last = Guest::query()->orderByDesc('id')->first();
        $next = $last ? ((int) $last->id + 1) : 1;

        return 'G'.str_pad((string) $next, 5, '0', STR_PAD_LEFT);
    }

    private function dynamicRatePayload(GuestMeal $meal): array
    {
        try {
            $rate = $this->guestRateForDate((string) $meal->meal_date);
            $amount = round($rate * (int) $meal->quantity, 2);

            return [$rate, $amount, false, null];
        } catch (\Throwable $e) {
            return [null, null, true, $e->getMessage()];
        }
    }

    private function appendDepartmentLedgerEntryForMeal(GuestMeal $meal, string $entryType, string $remarks): void
    {
        $guest = $meal->guest()->first();
        if (! $guest || ! $guest->department_id) {
            throw new \RuntimeException('Guest department is required for department ledger posting');
        }

        $this->appendDepartmentLedgerEntry(
            departmentId: (int) $guest->department_id,
            mealDate: $meal->meal_date,
            entryType: $entryType,
            amount: (float) $meal->amount,
            referenceId: (int) $meal->id,
            remarks: $remarks
        );
    }

    private function appendDepartmentLedgerEntry(int $departmentId, mixed $mealDate, string $entryType, float $amount, int $referenceId, string $remarks): void
    {
        DepartmentLedger::query()->create([
            'department_id' => $departmentId,
            'mess_id' => null,
            'entry_date' => Carbon::parse((string) $mealDate)->toDateString(),
            'entry_type' => strtoupper($entryType),
            'amount' => round($amount, 2),
            'reference_type' => GuestMeal::class,
            'reference_id' => $referenceId,
            'remarks' => $remarks,
        ]);
    }

    private function csvRows(Request $request): array
    {
        $rows = [];
        $file = fopen($request->file('file')->getRealPath(), 'r');
        $headers = fgetcsv($file) ?: [];
        $headers = array_map(fn ($header) => strtolower(trim((string) $header)), $headers);

        while (($line = fgetcsv($file)) !== false) {
            if (! array_filter($line, fn ($value) => trim((string) $value) !== '')) {
                continue;
            }

            $rows[] = array_combine($headers, array_pad($line, count($headers), null));
        }

        fclose($file);

        return $rows;
    }

    private function nullableText(mixed $value): ?string
    {
        $text = trim((string) ($value ?? ''));

        return $text === '' ? null : $text;
    }

    private function toBoolean(mixed $value): bool
    {
        return in_array(strtolower(trim((string) $value)), ['1', 'true', 'yes', 'y'], true);
    }

    private function resolveDepartmentId(array $row): ?int
    {
        if (! empty($row['department_id'])) {
            return (int) $row['department_id'];
        }

        $code = $this->nullableText($row['department_code'] ?? null);
        if ($code) {
            return Department::query()->where('code', $code)->value('id');
        }

        $name = $this->nullableText($row['department'] ?? ($row['department_name'] ?? null));
        if ($name) {
            return Department::query()->where('name', $name)->value('id');
        }

        return null;
    }

    private function resolveMemberId(array $row): ?int
    {
        if (! empty($row['host_member_id'])) {
            return (int) $row['host_member_id'];
        }

        $memberCode = $this->nullableText($row['host_member_code'] ?? ($row['member_code'] ?? null));
        if ($memberCode) {
            return Member::query()->where('member_code', $memberCode)->value('id');
        }

        return null;
    }

    private function resolveGuestId(array $row): ?int
    {
        if (! empty($row['guest_id'])) {
            return (int) $row['guest_id'];
        }

        $guestCode = $this->nullableText($row['guest_code'] ?? null);
        if ($guestCode) {
            return Guest::query()->where('guest_code', $guestCode)->value('id');
        }

        return null;
    }
}
