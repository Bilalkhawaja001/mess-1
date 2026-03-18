<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Extras\StoreExtraRequest;
use App\Models\Extra;
use App\Models\Member;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ExtraController extends Controller
{
    public function index(): View
    {
        $members = Member::query()->where('is_active', true)->orderBy('member_code')->get();
        $rows = Extra::query()->with('member')->orderByDesc('extra_date')->limit(200)->get();
        return view('admin.extras.index', compact('members', 'rows'));
    }

    public function store(StoreExtraRequest $request): RedirectResponse
    {
        Extra::query()->create([
            'extra_date'=>$request->input('extra_date'),
            'member_id'=>$request->input('member_id'),
            'description'=>$request->input('description'),
            'amount'=>$request->input('amount'),
            'posted_by_user_id'=>Auth::id(),
        ]);
        return redirect()->route('admin.extras.index')->with('success', 'Extra added.');
    }
}
