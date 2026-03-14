<?php

namespace App\Services;

use App\Models\Entities\Account;
use App\Models\Entities\ItemTransaction;
use App\Models\Entities\PaymentTransaction;
use App\Models\Entities\Tax;
use App\Models\Entities\UserCurrency;
use App\Models\Repositories\AccountRepo;
use App\Models\Repositories\TransactionRepo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class TransactionBulkService
{
    private TransactionRepo $transactionRepo;
    private AccountRepo $accountRepo;

    public function __construct(TransactionRepo $transactionRepo, AccountRepo $accountRepo)
    {
        $this->transactionRepo = $transactionRepo;
        $this->accountRepo = $accountRepo;
    }

    /**
     * Process bulk transaction creation
     *
     * @param array $rows Array of transaction data
     * @param \App\Models\Entities\User $user Authenticated user
     * @param bool $dryRun If true, validate without persisting
     * @return array ['total' => int, 'created' => int, 'failed' => int, 'results' => array]
     */
    public function processBulk(array $rows, $user, bool $dryRun = false): array
    {
        $total = count($rows);
        $created = 0;
        $failed = 0;
        $results = [];

        foreach ($rows as $index => $rowData) {
            $clientRowId = $rowData['client_row_id'] ?? null;

            try {
                // Validate row structure
                $validator = $this->validateRow($rowData);

                if ($validator->fails()) {
                    $failed++;
                    $results[] = [
                        'index' => $index,
                        'client_row_id' => $clientRowId,
                        'ok' => false,
                        'errors' => $validator->errors()->getMessages(),
                    ];
                    continue;
                }

                // Validate business rules
                $businessValidation = $this->validateBusinessRules($rowData, $user);
                if (!$businessValidation['ok']) {
                    $failed++;
                    $results[] = [
                        'index' => $index,
                        'client_row_id' => $clientRowId,
                        'ok' => false,
                        'errors' => $businessValidation['errors'],
                    ];
                    continue;
                }

                if ($dryRun) {
                    // Dry run: just validation, no persistence
                    $results[] = [
                        'index' => $index,
                        'client_row_id' => $clientRowId,
                        'ok' => true,
                        'transaction_id' => null,
                        'dry_run' => true,
                    ];
                    continue;
                }

                // Create transaction (each in its own DB transaction for partial success)
                DB::beginTransaction();
                try {
                    $transaction = $this->createTransaction($rowData, $user);
                    DB::commit();

                    $created++;
                    $results[] = [
                        'index' => $index,
                        'client_row_id' => $clientRowId,
                        'ok' => true,
                        'transaction_id' => $transaction->id,
                    ];
                } catch (\Exception $e) {
                    DB::rollBack();
                    $failed++;
                    $results[] = [
                        'index' => $index,
                        'client_row_id' => $clientRowId,
                        'ok' => false,
                        'errors' => ['general' => [$e->getMessage()]],
                    ];
                }

            } catch (\Exception $e) {
                Log::error('Bulk transaction row processing error', [
                    'index' => $index,
                    'client_row_id' => $clientRowId,
                    'error' => $e->getMessage(),
                ]);
                $failed++;
                $results[] = [
                    'index' => $index,
                    'client_row_id' => $clientRowId,
                    'ok' => false,
                    'errors' => ['general' => [$e->getMessage()]],
                ];
            }
        }

        return [
            'total' => $total,
            'created' => $created,
            'failed' => $failed,
            'results' => $results,
        ];
    }

    /**
     * Validate row structure
     */
    private function validateRow(array $rowData)
    {
        return Validator::make($rowData, [
            'name' => 'required|max:100',
            'amount' => 'nullable|numeric',
            'description' => 'nullable|max:255',
            'date' => 'required|date',
            'provider_id' => 'nullable|exists:providers,id',
            'url_file' => 'nullable|string',
            'rate_id' => 'nullable|integer',
            'transaction_type_id' => 'nullable|exists:transaction_types,id',
            'amount_tax' => 'nullable|numeric',
            'category_id' => 'nullable|exists:categories,id',
            'active' => 'sometimes|boolean',
            'include_in_balance' => 'sometimes|boolean',
            // Items
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
            // Payments (required)
            'payments' => 'required|array|min:1',
            'payments.*.account_id' => 'required_with:payments|exists:accounts,id',
            'payments.*.amount' => 'required_with:payments|numeric',
            'payments.*.rate' => 'nullable|numeric',
            'payments.*.is_current' => 'nullable|boolean',
            'payments.*.is_official' => 'nullable|boolean',
            'payments.*.current_rate' => 'nullable|boolean',
        ]);
    }

    /**
     * Validate business rules (authorization, cardinality, amounts)
     */
    private function validateBusinessRules(array $rowData, $user): array
    {
        $items = $rowData['items'] ?? [];
        $payments = $rowData['payments'] ?? [];

        // Authorization: check account ownership
        $accountsToCheck = [];
        foreach ($payments as $pm) {
            if (isset($pm['account_id'])) {
                $accountsToCheck[] = (int) $pm['account_id'];
            }
        }
        $accountsToCheck = array_values(array_unique(array_filter($accountsToCheck)));

        if (!empty($accountsToCheck) && !$user->isAdmin() && !app()->environment('testing')) {
            $allowed = $user->accounts()
                ->whereIn('accounts.id', $accountsToCheck)
                ->pluck('accounts.id')
                ->all();
            $denied = array_values(array_diff($accountsToCheck, $allowed));
            if (!empty($denied)) {
                return [
                    'ok' => false,
                    'errors' => ['payments' => ['You do not have permission to operate on one or more accounts']],
                ];
            }
        }

        // Validate cardinality rules (transfer vs simple)
        $paymentsCount = count($payments);
        $hasPos = false;
        $hasNeg = false;
        foreach ($payments as $pm) {
            $amt = isset($pm['amount']) ? (float) $pm['amount'] : 0.0;
            if ($amt > 0) $hasPos = true;
            if ($amt < 0) $hasNeg = true;
        }
        $isTransferLike = $hasPos && $hasNeg;

        if ($isTransferLike && $paymentsCount !== 2) {
            return [
                'ok' => false,
                'errors' => ['payments' => ['Transfers must have exactly 2 payments with opposite signs']],
            ];
        }

        // Derive and validate amounts
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

        $providedAmount = $rowData['amount'] ?? null;
        if (empty($items) && $providedAmount === null) {
            return [
                'ok' => false,
                'errors' => ['amount' => ['Amount is required when items are not provided']],
            ];
        }

        // If both provided and derived exist, they must match
        if (!empty($items) && $providedAmount !== null && $derivedAmount !== null) {
            $prov = (float) $providedAmount;
            $absDiff = abs(abs($prov) - $derivedAmount);

            $allItemsNegative = collect($items)->every(function($it){
                return ((float)($it['amount'] ?? 0)) < 0;
            });

            if ($allItemsNegative) {
                $negSum = 0.0;
                foreach ($items as $it) {
                    $negSum += abs((float)($it['amount'] ?? 0));
                }
                $derivedNormalized = round($negSum, 2);
                if (abs(abs($prov) - $derivedNormalized) > 0.01) {
                    return [
                        'ok' => false,
                        'errors' => ['amount' => ['Provided amount must equal items total']],
                    ];
                }
            } else {
                if ($absDiff > 0.01) {
                    return [
                        'ok' => false,
                        'errors' => ['amount' => ['Provided amount must equal items total']],
                    ];
                }
            }
        }

        // Validate payment amounts match transaction amount (if not transfer)
        if (!empty($payments)) {
            $sumUser = 0.0;
            $userCurrencyIdsByIdx = [];

            foreach ($payments as $idx => $pm) {
                $accAmt = isset($pm['amount']) ? (float) $pm['amount'] : 0.0;
                $accId = isset($pm['account_id']) ? (int)$pm['account_id'] : null;
                $providedRate = isset($pm['rate']) && $pm['rate'] !== null ? (float)$pm['rate'] : null;

                $markCurrent = array_key_exists('is_current', $pm)
                    ? $this->toBoolOrNull($pm['is_current'])
                    : (array_key_exists('current_rate', $pm) ? $this->toBoolOrNull($pm['current_rate']) : null);
                $markOfficial = array_key_exists('is_official', $pm) ? $this->toBoolOrNull($pm['is_official']) : null;

                $tmpUserCurrencyId = null;
                $rate = $this->resolveUserCurrencyRate((int)$user->id, (int)$accId, $providedRate, $markCurrent, $markOfficial, $tmpUserCurrencyId);

                if ($rate === 0.0) {
                    return [
                        'ok' => false,
                        'errors' => ['payments' => ['Rate cannot be zero in payments']],
                    ];
                }

                $userAmt = $accAmt / $rate;
                $sumUser += $userAmt;
                if ($tmpUserCurrencyId) {
                    $userCurrencyIdsByIdx[$idx] = (int)$tmpUserCurrencyId;
                }
            }

            $sumUser = round($sumUser, 2);
            $finalAmount = $derivedAmount ?? $providedAmount ?? 0.0;
            $finalAmount = round((float) $finalAmount, 2);

            if (!$isTransferLike) {
                $absMatches = abs(abs($sumUser) - abs($finalAmount)) <= 0.01;
                if (!$absMatches) {
                    return [
                        'ok' => false,
                        'errors' => ['payments' => ['Payments total (after conversion) must equal transaction amount']],
                    ];
                }
            }
        }

        return ['ok' => true];
    }

    /**
     * Create transaction and related records
     */
    private function createTransaction(array $rowData, $user)
    {
        $items = $rowData['items'] ?? [];
        $payments = $rowData['payments'] ?? [];

        // Derive amount
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
        $providedAmount = $rowData['amount'] ?? null;

        $data = [
            'name' => $rowData['name'],
            'amount' => $derivedAmount ?? $providedAmount,
            'description' => $rowData['description'] ?? null,
            'date' => $rowData['date'],
            'provider_id' => $rowData['provider_id'] ?? null,
            'url_file' => $rowData['url_file'] ?? null,
            'rate_id' => $rowData['rate_id'] ?? null,
            'transaction_type_id' => $rowData['transaction_type_id'] ?? null,
            'amount_tax' => $rowData['amount_tax'] ?? null,
            'account_id' => $rowData['account_id'] ?? null,
            'category_id' => $rowData['category_id'] ?? null,
            'user_id' => $user->id,
            'include_in_balance' => $rowData['include_in_balance'] ?? true,
        ];

        if (isset($rowData['active'])) {
            $data['active'] = (bool) $rowData['active'];
        }

        // Create transaction
        $transaction = $this->transactionRepo->store($data);

        // Create items
        foreach ($items as $it) {
            if (!empty($it['tax_id'])) {
                $tax = Tax::find($it['tax_id']);
                if (!$tax || !in_array($tax->applies_to ?? 'item', ['item','both'], true)) {
                    throw new \Exception('Selected tax does not apply to items');
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
                'category_id' => $it['category_id'] ?? $rowData['category_id'] ?? null,
                'item_category_id' => $it['item_category_id'] ?? null,
                'user_id' => $it['user_id'] ?? $transaction->user_id,
                'custom_name' => $it['custom_name'] ?? null,
                'active' => 1,
            ];
            ItemTransaction::create($payload);
        }

        // Create payments
        $userCurrencyIdsByIdx = [];
        foreach ($payments as $idx => $pm) {
            $accId = (int)$pm['account_id'];
            $accAmt = (float)$pm['amount'];
            $providedRate = isset($pm['rate']) && $pm['rate'] !== null ? (float)$pm['rate'] : null;

            $markCurrent = array_key_exists('is_current', $pm)
                ? $this->toBoolOrNull($pm['is_current'])
                : (array_key_exists('current_rate', $pm) ? $this->toBoolOrNull($pm['current_rate']) : null);
            $markOfficial = array_key_exists('is_official', $pm) ? $this->toBoolOrNull($pm['is_official']) : null;

            $tmpUserCurrencyId = null;
            $this->resolveUserCurrencyRate((int)$user->id, $accId, $providedRate, $markCurrent, $markOfficial, $tmpUserCurrencyId);

            $payload = [
                'transaction_id' => $transaction->id,
                'account_id' => $accId,
                'user_currency_id' => $tmpUserCurrencyId,
                'amount' => $accAmt,
                'active' => 1,
            ];
            PaymentTransaction::create($payload);
        }

        // Recalculate balances
        $transaction->load(['provider','rate','user','account','transactionType','category','itemTransactions','paymentTransactions.account']);

        $accountIds = collect($transaction->paymentTransactions)->pluck('account_id')->filter()->unique()->values()->all();
        foreach ($accountIds as $accId) {
            try {
                $this->accountRepo->recalcAndStoreFromInitialByType((int)$accId);
            } catch (\Exception $e) {
                Log::error('Balance recalculation error', ['account_id' => $accId, 'error' => $e->getMessage()]);
            }
        }

        return $transaction;
    }

    /**
     * Helper: convert value to bool or null
     */
    private function toBoolOrNull($val): ?bool
    {
        if ($val === null) return null;
        if (is_bool($val)) return $val;
        if (is_int($val)) return $val === 1;
        if (is_float($val)) return ((int)$val) === 1;
        if (is_string($val)) {
            $v = strtolower(trim($val));
            if (in_array($v, ['1','true','yes','on'], true)) return true;
            if (in_array($v, ['0','false','no','off',''], true)) return false;
        }
        return null;
    }

    /**
     * Resolve effective rate for a payment
     */
    private function resolveUserCurrencyRate(int $userId, int $accountId, ?float $providedRate, ?bool $markCurrent, ?bool $markOfficial = null, ?int &$outUserCurrencyId = null): float
    {
        $account = $accountId ? Account::find($accountId) : null;
        if (!$account || !$account->currency_id) {
            return (float) ($providedRate ?? 1.0);
        }
        $currencyId = (int) $account->currency_id;

        if ($providedRate !== null) {
            $record = UserCurrency::firstOrCreate(
                [
                    'user_id' => $userId,
                    'currency_id' => $currencyId,
                    'current_rate' => $providedRate,
                ],
                [
                    'is_current' => false,
                    'is_official' => false,
                ]
            );

            if ($markCurrent === true) {
                UserCurrency::where('user_id', $userId)
                    ->where('currency_id', $currencyId)
                    ->update(['is_current' => false]);
                $record->is_current = true;
            } elseif ($markCurrent === false) {
                $record->is_current = false;
            }

            if ($markOfficial === true) {
                $record->is_official = true;
                if (empty($record->official_at)) {
                    $record->official_at = now();
                }
            } elseif ($markOfficial === false) {
                $record->is_official = false;
            }

            $record->save();
            if ($outUserCurrencyId !== null || $outUserCurrencyId === null) {
                $outUserCurrencyId = (int)$record->id;
            }
            return (float) $providedRate;
        }

        // No provided rate: try user's current
        $current = UserCurrency::where('user_id', $userId)
            ->where('currency_id', $currencyId)
            ->where('is_current', true)
            ->where('is_official', true)
            ->orderByDesc('updated_at')
            ->first();
        if (!$current) {
            $current = UserCurrency::where('user_id', $userId)
                ->where('currency_id', $currencyId)
                ->where('is_current', true)
                ->orderByDesc('updated_at')
                ->first();
        }
        if ($current && $current->current_rate && (float)$current->current_rate > 0) {
            return (float) $current->current_rate;
        }
        return 1.0;
    }
}
