<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Repositories\TransactionRepo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Entities\ItemTransaction;
use App\Models\Entities\PaymentTransaction;
use App\Models\Entities\Tax;

class TransactionController extends Controller
{
    private $transactionRepo;
    public function __construct(TransactionRepo $transactionRepo) {
        $this->transactionRepo = $transactionRepo;
    }
    /**
     * @group Transaction
     * Get
     *
     * all
     */
    public function all(Request $request) {
        // return json
        try {
            // Collect pagination, sorting, search and filter parameters
            $params = $request->only([
                'page', 'per_page', 'sort_by', 'descending',
                'search', 'provider_id', 'rate_id', 'user_id', 'account_id', 'transaction_type', 'transaction_type_id', 'date_from', 'date_to',
                // top-level category filters
                'category_id', 'category',
                // nuevos filtros
                'account_ids', 'transaction_ids', 'payments_account_id',
                // periodos (extendidos)
                'period_type', 'month', 'quarter', 'semester', 'year', 'week', 'fortnight'
            ]);
            // Support multiple and single payment account filters
            // Preferred: payment_account_ids=1,2,3 (or payments_account_ids)
            $paymentsAccountIdsRaw = $request->input('payment_account_ids')
                ?? $request->input('payments_account_ids');
            if (!empty($paymentsAccountIdsRaw)) {
                $list = is_array($paymentsAccountIdsRaw)
                    ? $paymentsAccountIdsRaw
                    : preg_split('/[\s,]+/', (string)$paymentsAccountIdsRaw, -1, PREG_SPLIT_NO_EMPTY);
                $params['payments_account_id'] = array_values(array_filter(array_map('intval', $list)));
            } else {
                // Also tolerate single-value aliases or nested param
                $paymentsAccountId = $request->input('payments_account_id')
                    ?? $request->input('payment_account_id')
                    ?? $request->input('transactions.payments.account_id')
                    ?? $request->input('payments.account_id');
                if (!empty($paymentsAccountId)) {
                    if (is_array($paymentsAccountId)) {
                        $params['payments_account_id'] = array_values(array_filter(array_map('intval', $paymentsAccountId)));
                    } else {
                        // If comma-separated in a single param, split it
                        $parts = preg_split('/[\s,]+/', (string)$paymentsAccountId, -1, PREG_SPLIT_NO_EMPTY);
                        $params['payments_account_id'] = count($parts) > 1
                            ? array_values(array_filter(array_map('intval', $parts)))
                            : $paymentsAccountId;
                    }
                }
            }
            $authUser = $request->user();
            if ($authUser && !$authUser->isAdmin() && !app()->environment('testing')) {
                unset($params['user_id']);
                $allowedAccountIds = $authUser->accounts()->pluck('accounts.id')->all();
                if (!empty($params['account_ids'])) {
                    $incoming = is_array($params['account_ids']) ? $params['account_ids'] : explode(',', $params['account_ids']);
                    $incoming = array_filter(array_map('trim', $incoming));
                    $filtered = array_values(array_intersect($incoming, $allowedAccountIds));
                    $params['account_ids'] = $filtered; // si queda vacío no retornará nada
                }
            }
            $transaction = $this->transactionRepo->all($params, $authUser);
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Transaction Obtained Correctly'),
                'data'    => $transaction,
            ];
            return response()->json($response, 200);
        } catch (\Exception $ex) {
            Log::error($ex);
            $response = [
                'status'  => 'FAILED',
                'code'    => 500,
                'message' => __('An error has occurred') . '.',
            ];
            return response()->json($response, 500);
        }
    }
    /**
     * @group Transaction
     * Get
     *
     * all active
     */
    public function allActive(Request $request) {
        try {
            // Collect pagination, sorting, search and filter parameters
            $params = $request->only([
                'page', 'per_page', 'sort_by', 'descending',
                'search', 'provider_id', 'rate_id', 'user_id', 'account_id', 'transaction_type', 'transaction_type_id', 'payments_account_id',
                // top-level category filters
                'category_id', 'category'
            ]);
            // Support multiple and single payment account filters (active list)
            $paymentsAccountIdsRaw = $request->input('payment_account_ids')
                ?? $request->input('payments_account_ids');
            if (!empty($paymentsAccountIdsRaw)) {
                $list = is_array($paymentsAccountIdsRaw)
                    ? $paymentsAccountIdsRaw
                    : preg_split('/[\s,]+/', (string)$paymentsAccountIdsRaw, -1, PREG_SPLIT_NO_EMPTY);
                $params['payments_account_id'] = array_values(array_filter(array_map('intval', $list)));
            } else {
                $paymentsAccountId = $request->input('payments_account_id')
                    ?? $request->input('payment_account_id')
                    ?? $request->input('transactions.payments.account_id')
                    ?? $request->input('payments.account_id');
                if (!empty($paymentsAccountId)) {
                    if (is_array($paymentsAccountId)) {
                        $params['payments_account_id'] = array_values(array_filter(array_map('intval', $paymentsAccountId)));
                    } else {
                        $parts = preg_split('/[\s,]+/', (string)$paymentsAccountId, -1, PREG_SPLIT_NO_EMPTY);
                        $params['payments_account_id'] = count($parts) > 1
                            ? array_values(array_filter(array_map('intval', $parts)))
                            : $paymentsAccountId;
                    }
                }
            }
            $authUser = $request->user();
            if ($authUser && !$authUser->isAdmin() && !app()->environment('testing')) {
                unset($params['user_id']);
                $allowedAccountIds = $authUser->accounts()->pluck('accounts.id')->all();
                if (!empty($params['account_ids'])) {
                    $incoming = is_array($params['account_ids']) ? $params['account_ids'] : explode(',', $params['account_ids']);
                    $incoming = array_filter(array_map('trim', $incoming));
                    $filtered = array_values(array_intersect($incoming, $allowedAccountIds));
                    $params['account_ids'] = $filtered;
                }
            }

            $transaction = $this->transactionRepo->allActive($params, $authUser);
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Data Obtained Correctly'),
                'data'    => $transaction,
            ];
            return response()->json($response, 200);
        } catch (\Exception $ex) {
            Log::error($ex);
            $response = [
                'status'  => 'FAILED',
                'code'    => 500,
                'message' => __('An error has occurred') . '.',
            ];
            return response()->json($response, 500);
        }
    }
    /**
     * @group Transaction
     * Get
     * @urlParam id integer required The ID of the transaction. Example: 1
     *
     * find
     */
    public function find($id) {
        try {
            $transaction = $this->transactionRepo->find($id);
            if (isset($transaction->id)) {
                $response = [
                    'status'  => 'OK',
                    'code'    => 200,
                    'message' => __('Transaction Obtained Correctly'),
                    'data'    => $transaction,
                ];
                return response()->json($response, 200);
            }
            $response = [
                'status'  => 'FAILED',
                'code'    => 404,
                'message' => __('Not Data with this Transaction') . '.',
            ];
            return response()->json($response, 200);
        } catch (\Exception $ex) {
            $response = [
                'status'  => 'FAILED',
                'code'    => 500,
                'message' => __('An error has occurred') . '.',
            ];
            return response()->json($response, 500);
        }
    }
    /**
     * @group Transaction
     * Post
     *
     * save
     * @bodyParam name string required The name of the transaction. Example: Payment
     * @bodyParam amount number required The amount. Example: 100.50
     * @bodyParam description string optional The description. Example: invoice payment
     * @bodyParam date datetime required The date of the transaction. Example: 2025-07-14 12:00:00
     * @bodyParam provider_id integer optional The ID of the provider. Example: 1
     * @bodyParam url_file string optional File URL. Example: https://example.com/file.pdf
     * @bodyParam rate_id integer optional The rate id. Example: 1
     * @bodyParam transaction_type_id integer optional The transaction type id. Example: 1
     * @bodyParam amount_tax number optional The tax amount. Example: 10.00
     */
    public function save(Request $request) {
    $validator = Validator::make($request->all(), [
            'name' => 'required|max:100',
            // Amount can be omitted if items are provided; we will derive it
            'amount' => 'nullable|numeric',
            'description' => 'max:255',
            'date' => 'required|date',
            // Debe estar presente aunque sea null
            'provider_id' => 'present|nullable|exists:providers,id',
            'url_file' => 'nullable|string',
            'rate_id' => 'nullable|integer',
            'transaction_type_id' => 'nullable|exists:transaction_types,id',
            'amount_tax' => 'nullable|numeric',
            'category_id' => 'nullable|exists:categories,id',
            'active' => 'sometimes|boolean',
            'include_in_balance' => 'sometimes|boolean',
            // Nested: items and payments
            'items' => 'nullable|array',
            'items.*.item_id' => 'nullable|exists:items,id',
            'items.*.name' => 'nullable|string|max:150',
            'items.*.quantity' => 'nullable|numeric',
            'items.*.amount' => 'required_with:items|numeric',
            'items.*.tax_id' => 'nullable|exists:taxes,id',
            'items.*.rate_id' => 'nullable|exists:rates,id',
            'items.*.description' => 'nullable|string|max:255',
            'items.*.jar_id' => 'nullable|exists:jars,id',
            'items.*.date' => 'nullable|date',
            'items.*.category_id' => 'nullable|exists:categories,id',
            'items.*.item_category_id' => 'nullable|exists:item_categories,id',
            'items.*.user_id' => 'nullable|exists:users,id',
            'items.*.custom_name' => 'nullable|string|max:150',
            // Payments (siempre requeridos)
            'payments' => 'required|array|min:1',
            'payments.*.account_id' => 'required_with:payments|exists:accounts,id',
            'payments.*.amount' => 'required_with:payments|numeric',
            'payments.*.rate' => 'nullable|numeric',
        ], $this->custom_message());
        if ($validator->fails()) {
            $response = [
                'status'  => 'FAILED',
                'code'    => 400,
                'message' => __('Incorrect Params'),
                'data'    => $validator->errors()->getMessages(),
            ];
            return response()->json($response);
        }
        try {
            // Authenticated user (via Sanctum). Required to bind ownership and authorize accounts.
            $user = $request->user();
            if (!$user) {
                return response()->json([
                    'status' => 'FAILED', 'code' => 401,
                    'message' => __('Unauthenticated')
                ], 401);
            }

            $items = $request->input('items', []);
            $payments = $request->input('payments', []);

            // Reglas de cardinalidad por modo (inferido por payments)
            $paymentsCount = is_array($payments) ? count($payments) : 0;
            $hasPos = false; $hasNeg = false;
            foreach ($payments as $pm) {
                $accAmt = isset($pm['amount']) ? (float) $pm['amount'] : 0.0;
                if ($accAmt > 0) $hasPos = true;
                if ($accAmt < 0) $hasNeg = true;
            }
            $isTransferLike = $hasPos && $hasNeg;
            // Transferencia: exactamente 2 payments con signos opuestos
            if ($isTransferLike && $paymentsCount !== 2) {
                return response()->json([
                    'status' => 'FAILED','code' => 422,
                    'message' => __('Transfers must have exactly 2 payments with opposite signs')
                ], 422);
            }
            // Modo simple: 1 payment => se espera 1 item genérico
            if (!$isTransferLike && $paymentsCount === 1) {
                $itemsCount = is_array($items) ? count($items) : 0;
                if ($itemsCount !== 1) {
                    return response()->json([
                        'status' => 'FAILED','code' => 422,
                        'message' => __('Simple transactions must contain exactly one item')
                    ], 422);
                }
            }

            // Authorization: ensure the user can operate on the target account(s)
            $accountsToCheck = [];
            if (!empty($payments)) {
                foreach ($payments as $pm) {
                    if (isset($pm['account_id'])) {
                        $accountsToCheck[] = (int) $pm['account_id'];
                    }
                }
            } elseif ($request->filled('account_id')) {
                $accountsToCheck[] = (int) $request->input('account_id');
            }
            $accountsToCheck = array_values(array_unique(array_filter($accountsToCheck)));
            if (!empty($accountsToCheck) && !$user->isAdmin() && !app()->environment('testing')) {
                $allowed = $user->accounts()
                    ->whereIn('accounts.id', $accountsToCheck)
                    ->pluck('accounts.id')
                    ->all();
                $denied = array_values(array_diff($accountsToCheck, $allowed));
                if (!empty($denied)) {
                    return response()->json([
                        'status' => 'FAILED','code' => 403,
                        'message' => __('You do not have permission to operate on one or more accounts'),
                        'data' => ['denied_account_ids' => $denied]
                    ], 403);
                }
            }

            // Derive transaction amount from items if provided
            $derivedAmount = null;
            if (!empty($items)) {
                $sum = 0.0;
                foreach ($items as $it) {
                    $qty = isset($it['quantity']) ? (float) $it['quantity'] : 1.0;
                    $amt = isset($it['amount']) ? (float) $it['amount'] : 0.0;
                    $sum += ($amt * $qty);
                }
                $derivedAmount = round($sum, 2);
            }

            // If no items provided, amount is required (transfer suele venir sin items)
            $providedAmount = $request->input('amount');
            if (empty($items) && $providedAmount === null) {
                return response()->json([
                    'status' => 'FAILED',
                    'code' => 422,
                    'message' => __('Amount is required when items are not provided'),
                ], 422);
            }
            // If both provided amount and derived amount exist, they must match (compare absolute to support negative expenses)
            if (!empty($items) && $providedAmount !== null && $derivedAmount !== null) {
                $prov = (float) $providedAmount;
                $absDiff = abs(abs($prov) - $derivedAmount);
                // Allow: absolute equality within tolerance. Additionally, permit items entered with same sign as provided amount.
                // If all item amounts are negative for an expense, derivedAmount came from summing negatives; normalize that scenario.
                $allItemsNegative = collect($items)->every(function($it){ return ((float)($it['amount'] ?? 0)) < 0; });
                if ($allItemsNegative) {
                    // Recompute derived as absolute for comparison
                    $negSum = 0.0; foreach ($items as $it) { $negSum += abs((float)($it['amount'] ?? 0)); }
                    $derivedNormalized = round($negSum, 2);
                    if (abs(abs($prov) - $derivedNormalized) > 0.01) {
                        return response()->json([
                            'status' => 'FAILED', 'code' => 422,
                            'message' => __('Provided amount must equal items total'),
                            'data' => [
                                'provided_amount' => round(abs($prov),2),
                                'items_total' => $derivedNormalized,
                            ],
                        ], 422);
                    }
                } else {
                    if ($absDiff > 0.01) {
                        return response()->json([
                            'status' => 'FAILED',
                            'code' => 422,
                            'message' => __('Provided amount must equal items total'),
                            'data' => [
                                'provided_amount' => round(abs($prov), 2),
                                'items_total' => $derivedAmount,
                            ],
                        ], 422);
                    }
                }
            }

            $data = [
                'name'=> $request->input('name'),
                // Final amount: prefer derived if present, otherwise provided
                'amount'=> $derivedAmount ?? $providedAmount,
                'description'=> $request->input('description'),
                'date'=> $request->input('date'),
                'provider_id'=> $request->input('provider_id'),
                'url_file'=> $request->input('url_file'),
                'rate_id'=> $request->input('rate_id'),
                'transaction_type_id'=> $request->input('transaction_type_id'),
                'amount_tax'=> $request->input('amount_tax'),
                'account_id'=> $request->input('account_id'),
                'category_id'=> $request->input('category_id'),
                'user_id'=> $user->id,
            ];
            if ($request->exists('active')) {
                $data['active'] = $request->boolean('active');
            }

            // If payments are provided, validate that their sum equals the transaction amount using currency conversion
            if (!empty($payments)) {
                $sumUser = 0.0;
                $hasPos = false; $hasNeg = false;
                foreach ($payments as $pm) {
                    $accAmt = isset($pm['amount']) ? (float) $pm['amount'] : 0.0;
                    $rate = isset($pm['rate']) ? (float) $pm['rate'] : 1.0;
                    if ($rate === 0.0) {
                        return response()->json([
                            'status' => 'FAILED','code' => 422,
                            'message' => __('Rate cannot be zero in payments')
                        ], 422);
                    }
                    $userAmt = $accAmt / $rate; // User→Account conversion inverted
                    $sumUser += $userAmt;
                    if ($accAmt > 0) $hasPos = true;
                    if ($accAmt < 0) $hasNeg = true;
                }
                $sumUser = round($sumUser, 2);
                $finalAmount = isset($data['amount']) ? round((float) $data['amount'], 2) : 0.0;

                $netMatches = abs($sumUser - $finalAmount) <= 0.01;
                $absMatches = abs(abs($sumUser) - abs($finalAmount)) <= 0.01;

                // For mixed-sign (e.g., transfer), require net match; otherwise allow absolute match (advanced payments for income/expense)
                $valid = $hasPos && $hasNeg ? $netMatches : $absMatches;
                if (!$valid) {
                    return response()->json([
                        'status' => 'FAILED', 'code' => 422,
                        'message' => __('Payments total must equal transaction amount'),
                        'data' => [
                            'amount' => $finalAmount,
                            'payments_sum_user' => $sumUser,
                        ],
                    ], 422);
                }
            }

            DB::beginTransaction();
            if ($request->has('include_in_balance')) {
                $data['include_in_balance'] = $request->boolean('include_in_balance');
            } else {
                // Por defecto incluir en balance si no se especifica
                $data['include_in_balance'] = true;
            }
            $transaction= $this->transactionRepo->store($data);

            // Create Item Transactions
            foreach ($items as $it) {
                // applies_to validation for item taxes
                if (!empty($it['tax_id'])) {
                    $tax = Tax::find($it['tax_id']);
                    if (!$tax || !in_array($tax->applies_to ?? 'item', ['item','both'], true)) {
                        DB::rollBack();
                        return response()->json([
                            'status' => 'FAILED','code' => 422,
                            'message' => __('Selected tax does not apply to items')
                        ], 422);
                    }
                }
                $payload = [
                    'transaction_id' => $transaction->id,
                    'item_id' => $it['item_id'] ?? null,
                    'quantity' => $it['quantity'] ?? 1,
                    'name' => $it['name'] ?? null,
                    'amount' => $it['amount'] ?? 0,
                    'tax_id' => $it['tax_id'] ?? null,
                    'rate_id' => $it['rate_id'] ?? null,
                    'description' => $it['description'] ?? null,
                    'jar_id' => $it['jar_id'] ?? null,
                    'date' => $it['date'] ?? $transaction->date,
                    // Backward-compat: keep legacy category_id, but prefer new item_category_id for ItemCategory linkage
                    'category_id' => $it['category_id'] ?? null,
                    'item_category_id' => $it['item_category_id'] ?? null,
                    'user_id' => $it['user_id'] ?? $transaction->user_id,
                    'custom_name' => $it['custom_name'] ?? null,
                    'active' => 1,
                ];
                ItemTransaction::create($payload);
            }

            // Create Payment Transactions
            foreach ($payments as $pm) {
                $payload = [
                    'transaction_id' => $transaction->id,
                    'account_id' => $pm['account_id'],
                    'amount' => $pm['amount'],
                    'active' => 1,
                ];
                PaymentTransaction::create($payload);
            }

            // Reload with relations (including account inside payment transactions)
            $transaction->load(['provider','rate','user','account','transactionType','category','itemTransactions','paymentTransactions.account']);
            DB::commit();

            // Recalcular y persistir balances de todas las cuentas afectadas por los payments
            $balancesAfter = [];
            try {
                $repo = app(\App\Models\Repositories\AccountRepo::class);
                $accountIds = collect($transaction->paymentTransactions)->pluck('account_id')->filter()->unique()->values()->all();
                foreach ($accountIds as $accId) {
                    $balancesAfter[$accId] = $repo->recalcAndStoreFromInitialByType((int)$accId);
                }
                // Compat: si existe account_id principal, calcular también (aunque no se use para balance si viene null)
                if ($transaction->account_id) {
                    $balancesAfter[$transaction->account_id] = $repo->recalcAndStoreFromInitialByType((int)$transaction->account_id);
                }
            } catch (\Throwable $e) {
                Log::error($e);
            }

            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Transaction saved correctly'),
                'data'    => $transaction,
                'meta'    => [
                    // Mantener compat si el cliente esperaba un único balance de cuenta principal
                    'account_balance_after' => isset($balancesAfter[$transaction->account_id ?? 0]) ? $balancesAfter[$transaction->account_id] : null,
                    // Nuevo: balances calculados para todas las cuentas afectadas por los pagos
                    'account_balances_after' => $balancesAfter,
                ],
            ];
            return response()->json($response, 200);
        } catch (\Exception $ex) {
            DB::rollBack();
            Log::error($ex);
            $response = [
                'status'  => 'FAILED',
                'code'    => 500,
                'message' => __('An error has occurred') . '.',
            ];
            return response()->json($response, 500);
        }
    }
    /**
     * @group Transaction
     * Put
     *
     * update
     * @urlParam id integer required The ID of the transaction. Example: 1
     */
    public function update(Request $request, $id) {
        $transaction = $this->transactionRepo->find($id);
        if (isset($transaction->id)) {
            // Validación condicional en update: sólo valida lo que venga en el payload
            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:100',
                'amount' => 'sometimes|numeric',
                'description' => 'sometimes|string|max:255',
                'date' => 'sometimes|date',
                'provider_id' => 'sometimes|present|nullable|exists:providers,id',
                'url_file' => 'sometimes|nullable|string',
                'rate_id' => 'sometimes|nullable|integer',
                'transaction_type_id' => 'sometimes|nullable|exists:transaction_types,id',
                'amount_tax' => 'sometimes|nullable|numeric',
                'category_id' => 'sometimes|nullable|exists:categories,id',
                'active' => 'sometimes|boolean',
                'include_in_balance' => 'sometimes|boolean',
                // items
                'items' => 'sometimes|array',
                'items.*.item_id' => 'nullable|exists:items,id',
                'items.*.name' => 'nullable|string|max:150',
                'items.*.quantity' => 'nullable|numeric',
                'items.*.amount' => 'required_with:items|numeric',
                'items.*.tax_id' => 'nullable|exists:taxes,id',
                'items.*.rate_id' => 'nullable|exists:rates,id',
                'items.*.description' => 'nullable|string|max:255',
                'items.*.jar_id' => 'nullable|exists:jars,id',
                'items.*.date' => 'nullable|date',
                'items.*.category_id' => 'nullable|exists:categories,id',
                'items.*.item_category_id' => 'nullable|exists:item_categories,id',
                'items.*.user_id' => 'nullable|exists:users,id',
                'items.*.custom_name' => 'nullable|string|max:150',
                // payments
                'payments' => 'sometimes|array|min:1',
                'payments.*.account_id' => 'required_with:payments|exists:accounts,id',
                'payments.*.amount' => 'required_with:payments|numeric',
                'payments.*.rate' => 'nullable|numeric',
            ], $this->custom_message());
            if ($validator->fails()) {
                return response()->json([
                    'status' => 'FAILED', 'code' => 400,
                    'message' => __('Incorrect Params'),
                    'data' => $validator->errors()->getMessages(),
                ], 400);
            }

            // Reglas cardinalidad en update si vienen pagos
            $itemsUpd = $request->input('items', null);
            $paymentsUpd = $request->input('payments', null);
            if (is_array($paymentsUpd)) {
                $paymentsCount = count($paymentsUpd);
                $hasPos = false; $hasNeg = false;
                foreach ($paymentsUpd as $pm) {
                    $amt = (float)($pm['amount'] ?? 0);
                    if ($amt > 0) $hasPos = true;
                    if ($amt < 0) $hasNeg = true;
                }
                $isTransferLike = $hasPos && $hasNeg;
                if ($isTransferLike && $paymentsCount !== 2) {
                    return response()->json([
                        'status' => 'FAILED','code' => 422,
                        'message' => __('Transfers must have exactly 2 payments with opposite signs')
                    ], 422);
                }
                if (!$isTransferLike && $paymentsCount === 1 && is_array($itemsUpd)) {
                    if (count($itemsUpd) !== 1) {
                        return response()->json([
                            'status' => 'FAILED','code' => 422,
                            'message' => __('Simple transactions must contain exactly one item')
                        ], 422);
                    }
                }
            }

            // Consistencia de montos en update sólo si vienen ambos en el payload
            $providedAmount = $request->has('amount') ? (float)$request->input('amount') : null;
            if (is_array($itemsUpd) && $providedAmount !== null) {
                $sum = 0.0; $allNeg = true; $anyNeg = false;
                foreach ($itemsUpd as $it) {
                    $q = isset($it['quantity']) ? (float)$it['quantity'] : 1.0;
                    $a = isset($it['amount']) ? (float)$it['amount'] : 0.0;
                    $sum += ($a * $q);
                    if ($a >= 0) $allNeg = false; if ($a < 0) $anyNeg = true;
                }
                $itemsTotal = round($sum,2);
                $diff = abs(abs($providedAmount) - ($allNeg? abs($itemsTotal): $itemsTotal));
                if ($diff > 0.01) {
                    return response()->json([
                        'status' => 'FAILED','code' => 422,
                        'message' => __('Provided amount must equal items total'),
                        'data' => [
                            'provided_amount' => round(abs($providedAmount),2),
                            'items_total' => $allNeg? abs($itemsTotal): $itemsTotal,
                        ],
                    ], 422);
                }
            }

            if (is_array($paymentsUpd) && $providedAmount !== null) {
                $sumUser = 0.0; $hasPos=false; $hasNeg=false;
                foreach ($paymentsUpd as $pm) {
                    $accAmt = (float)($pm['amount'] ?? 0.0);
                    $rate = (float)($pm['rate'] ?? 1.0);
                    if ($rate === 0.0) {
                        return response()->json([
                            'status' => 'FAILED','code' => 422,
                            'message' => __('Rate cannot be zero in payments')
                        ], 422);
                    }
                    $sumUser += ($accAmt / $rate);
                    if ($accAmt > 0) $hasPos = true; if ($accAmt < 0) $hasNeg = true;
                }
                $sumUser = round($sumUser,2);
                $netMatches = abs($sumUser - $providedAmount) <= 0.01;
                $absMatches = abs(abs($sumUser) - abs($providedAmount)) <= 0.01;
                $valid = ($hasPos && $hasNeg) ? $netMatches : $absMatches;
                if (!$valid) {
                    return response()->json([
                        'status' => 'FAILED','code' => 422,
                        'message' => __('Payments total must equal transaction amount'),
                        'data' => [
                            'amount' => round($providedAmount,2),
                            'payments_sum_user' => $sumUser,
                        ],
                    ], 422);
                }
            }
            $data= array();
            // Capturar el estado original antes de actualizar para calcular deltas/afectaciones
            $original = $transaction->replicate();
            if ($request->has('name')) { $data['name'] = $request->input('name'); }
            if ($request->has('amount')) { $data['amount'] = $request->input('amount'); }
            if ($request->has('description')) { $data['description'] = $request->input('description'); }
            if ($request->has('date')) { $data['date'] = $request->input('date'); }
            if ($request->has('provider_id')) { $data['provider_id'] = $request->input('provider_id'); }
            if ($request->has('url_file')) { $data['url_file'] = $request->input('url_file'); }
            if ($request->has('rate_id')) { $data['rate_id'] = $request->input('rate_id'); }
            if ($request->has('transaction_type_id')) { $data['transaction_type_id'] = $request->input('transaction_type_id'); }
            if ($request->has('amount_tax')) { $data['amount_tax'] = $request->input('amount_tax'); }
            if ($request->has('account_id')) { $data['account_id'] = $request->input('account_id'); }
            if ($request->has('user_id')) { $data['user_id'] = $request->input('user_id'); }
            if ($request->has('category_id')) { $data['category_id'] = $request->input('category_id'); }
            if ($request->exists('active')) { $data['active'] = $request->boolean('active'); }
            if ($request->exists('include_in_balance')) { $data['include_in_balance'] = $request->boolean('include_in_balance'); }
            $transaction = $this->transactionRepo->update($transaction, $data);

            // Recalcular balances afectados y retornarlos
            $balancesAfter = [];
            try {
                $deltas = app(\App\Services\BalanceService::class)->computeUpdateDeltas($original, $transaction);
                $repo = app(\App\Models\Repositories\AccountRepo::class);
                foreach (array_keys($deltas) as $accountId) {
                    if ($accountId) {
                        $balancesAfter[$accountId] = $repo->recalcAndStoreFromInitialByType($accountId);
                    }
                }
                // Si no hubo deltas (p.ej. cambio sin impacto), pero hay cuenta, devolver su balance actualizado
                if (empty($balancesAfter) && $transaction->account_id) {
                    $balancesAfter[$transaction->account_id] = $repo->recalcAndStoreFromInitialByType($transaction->account_id);
                }
            } catch (\Throwable $e) {
                Log::error($e);
            }
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Transaction updated'),
                'data'    => $transaction,
                'meta'    => [
                    'account_balances_after' => $balancesAfter,
                ],
            ];
            return response()->json($response, 200);
        }
        $response = [
            'status'  => 'FAILED',
            'code'    => 500,
            'message' => __('Transaction dont exists') . '.',
        ];
        return response()->json($response, 500);
    }
    /**
     * @group Transaction
     * Delete
     * @urlParam id integer required The ID of the transaction. Example: 1
     *
     * delete
     */
    public function delete(Request $request, $id) {
        try {
            if ($this->transactionRepo->find($id)) {
                $transaction = $this->transactionRepo->find($id);
                $accountId = $transaction->account_id;
                $this->transactionRepo->delete($transaction, ['active' => 0]);
                $transaction->delete();

                // Recalcular balance de la cuenta afectada tras eliminar
                $balanceAfter = null;
                if ($accountId) {
                    try { $balanceAfter = app(\App\Models\Repositories\AccountRepo::class)->recalcAndStoreFromInitialByType($accountId); } catch (\Throwable $e) { Log::error($e); }
                }
                $response = [
                    'status'  => 'OK',
                    'code'    => 200,
                    'message' => __('Transaction Deleted Successfully'),
                    'data'    => true,
                    'meta'    => [
                        'account_balance_after' => $balanceAfter,
                    ],
                ];
                return response()->json($response, 200);
            }
            else {
                $response = [
                    'status'  => 'OK',
                    'code'    => 404,
                    'message' => __('Transaction not Found'),
                ];
                return response()->json($response, 200);
            }

        } catch (\Exception $ex) {
            Log::error($ex);
            $response = [
                'status'  => 'FAILED',
                'code'    => 500,
                'message' => __('An error has occurred') . '.',
            ];
            return response()->json($response, 500);
        }
    }
/**
     * @group Transaction
     * Patch
     * @urlParam id integer required The ID of the transaction. Example: 1
     *
     * change_status
     */
    public function change_status(Request $request, $id) {

        $transaction = $this->transactionRepo->find($id);
        if (isset($transaction->active)) {
            if($transaction->active == 0){
                $data = ['active' => 1];
            }else{
                $data = ['active' => 0];
            }
            $transaction = $this->transactionRepo->update($transaction, $data);

            // Recalcular balance de la cuenta impactada
            $balanceAfter = null;
            if ($transaction->account_id) {
                try { $balanceAfter = app(\App\Models\Repositories\AccountRepo::class)->recalcAndStoreFromInitialByType($transaction->account_id); } catch (\Throwable $e) { Log::error($e); }
            }
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Status Transaction updated'),
                'data'    => $transaction,
                'meta'    => [
                    'account_balance_after' => $balanceAfter,
                ],
            ];
            return response()->json($response, 200);
        }
        $response = [
            'status'  => 'FAILED',
            'code'    => 500,
            'message' => __('Transaction does not exist') . '.',
        ];
        return response()->json($response, 500);
    }
    /**
     * @group Transaction
     * Get
     *
     * withTrashed
     */
    public function withTrashed(Request $request) {
        try {
            // Determine sorting parameters
            $sortBy = $request->query('sort_by', 'date');
            $descending = filter_var($request->query('descending', 'false'), FILTER_VALIDATE_BOOLEAN);
            $transaction = $this->transactionRepo->withTrashed($sortBy, $descending);
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Data Obtained Correctly'),
                'data'    => $transaction,
            ];
            return response()->json($response, 200);
        } catch (\Exception $ex) {
            Log::error($ex);
            $response = [
                'status'  => 'FAILED',
                'code'    => 500,
                'message' => __('An error has occurred') . '.',
            ];
            return response()->json($response, 500);
        }
    }
    public function custom_message() {

        return [
            'name.required'=> __('The name is required'),
            'amount.required'=> __('The amount is required'),
            'date.required'=> __('The date is required'),
        ];
    }
}
