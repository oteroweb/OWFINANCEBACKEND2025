<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Entities\Debt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DebtController extends Controller
{
    public function index(Request $request)
    {
        $user  = $request->user();

        $allowedSorts = ['priority', 'created_at', 'balance', 'next_due_date', 'status', 'name'];
        $sortBy = in_array($request->query('sort_by'), $allowedSorts) ? $request->query('sort_by') : 'priority';
        $desc   = filter_var($request->query('descending', 'false'), FILTER_VALIDATE_BOOLEAN);

        $query = Debt::where('user_id', $user->id)
            ->orderBy($sortBy, $desc ? 'desc' : 'asc')
            ->orderBy('created_at');

        // Meta always computed over ALL debts (not limited by per_page)
        $all = Debt::where('user_id', $user->id)->get();
        $totalBalance  = $all->sum('balance');
        $totalMonthly  = $all->sum('next_due_amount');
        $lateCount     = $all->where('status', 'late')->count();
        $casheaCount   = $all->where('provider', 'cashea')->count();

        $perPage = (int) $request->query('per_page', 0);
        $debts   = $perPage > 0 ? $query->limit($perPage)->get() : $query->get();

        return response()->json([
            'status' => 'OK',
            'data'   => $debts,
            'meta'   => [
                'total_balance'  => $totalBalance,
                'total_monthly'  => $totalMonthly,
                'late_count'     => $lateCount,
                'cashea_count'   => $casheaCount,
                'count'          => $all->count(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name'               => 'required|string|max:100',
            'provider'           => 'nullable|in:cashea,card,loan,personal',
            'merchant'           => 'nullable|string|max:100',
            'original_amount'    => 'required|numeric|min:0',
            'balance'            => 'nullable|numeric|min:0',
            'next_due_amount'    => 'nullable|numeric|min:0',
            'next_due_date'      => 'nullable|date',
            'total_installments' => 'nullable|integer|min:1',
            'paid_installments'  => 'nullable|integer|min:0',
            'rate'               => 'nullable|string|max:32',
            'status'             => 'nullable|in:on-track,due-soon,late,paid',
            'notes'              => 'nullable|string|max:1000',
            'priority'           => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'message' => $validator->errors()->first()], 422);
        }

        $debt = Debt::create([
            'user_id'            => $user->id,
            'name'               => $request->name,
            'provider'           => $request->provider ?? 'loan',
            'merchant'           => $request->merchant,
            'original_amount'    => $request->original_amount,
            'balance'            => $request->balance ?? $request->original_amount,
            'next_due_amount'    => $request->next_due_amount ?? 0,
            'next_due_date'      => $request->next_due_date,
            'total_installments' => $request->total_installments,
            'paid_installments'  => $request->paid_installments ?? 0,
            'rate'               => $request->rate,
            'status'             => $request->status ?? 'on-track',
            'notes'              => $request->notes,
            'priority'           => $request->priority ?? 0,
        ]);

        return response()->json(['status' => 'OK', 'data' => $debt], 201);
    }

    public function show(Request $request, int $id)
    {
        $debt = Debt::where('user_id', $request->user()->id)->findOrFail($id);
        return response()->json(['status' => 'OK', 'data' => $debt]);
    }

    public function update(Request $request, int $id)
    {
        $debt = Debt::where('user_id', $request->user()->id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'name'               => 'sometimes|string|max:100',
            'provider'           => 'nullable|in:cashea,card,loan,personal',
            'merchant'           => 'nullable|string|max:100',
            'original_amount'    => 'sometimes|numeric|min:0',
            'balance'            => 'nullable|numeric|min:0',
            'next_due_amount'    => 'nullable|numeric|min:0',
            'next_due_date'      => 'nullable|date',
            'total_installments' => 'nullable|integer|min:1',
            'paid_installments'  => 'nullable|integer|min:0',
            'rate'               => 'nullable|string|max:32',
            'status'             => 'nullable|in:on-track,due-soon,late,paid',
            'notes'              => 'nullable|string|max:1000',
            'priority'           => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'message' => $validator->errors()->first()], 422);
        }

        $debt->update($request->only([
            'name', 'provider', 'merchant', 'original_amount', 'balance',
            'next_due_amount', 'next_due_date', 'total_installments',
            'paid_installments', 'rate', 'status', 'notes', 'priority',
        ]));

        return response()->json(['status' => 'OK', 'data' => $debt->fresh()]);
    }

    public function payInstallment(Request $request, int $id)
    {
        $debt = Debt::where('user_id', $request->user()->id)->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:0.01',
        ]);

        if ($validator->fails()) {
            return response()->json(['status' => 'FAILED', 'message' => $validator->errors()->first()], 422);
        }

        $debt->balance = max(0, $debt->balance - $request->amount);
        $debt->paid_installments = $debt->paid_installments + 1;

        if ($debt->balance <= 0) {
            $debt->status  = 'paid';
            $debt->balance = 0;
        }

        $debt->save();

        return response()->json(['status' => 'OK', 'data' => $debt->fresh()]);
    }

    public function destroy(Request $request, int $id)
    {
        $debt = Debt::where('user_id', $request->user()->id)->findOrFail($id);
        $debt->delete();
        return response()->json(['status' => 'OK', 'message' => 'Deuda eliminada']);
    }
}
