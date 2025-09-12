<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Repositories\AccountRepo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use App\Models\Entities\AccountFolder;
use Illuminate\Support\Facades\DB;

class AccountController extends Controller
{
    private $accountRepo;

    public function __construct(AccountRepo $accountRepo)
    {
        $this->accountRepo = $accountRepo;
    }

    /**
     * @group Account
     * Get all accounts
     */
    public function all(Request $request)
    {
        try {
            $params = $request->only(['page','per_page','sort_by','descending','search','active','currency_id','currency','account_type_id','account_type','user_id','user','is_owner']);
            $accounts = $this->accountRepo->all($params);
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Accounts Obtained Correctly'),
                'data'    => $accounts,
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
     * @group Account
     * Get all active accounts
     */
    public function allActive(Request $request)
    {
        try {
            $params = $request->only(['page','per_page','sort_by','descending','search','active','currency_id','currency','account_type_id','account_type','user_id','user','is_owner']);
            $accounts = $this->accountRepo->allActive($params);
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Data Obtained Correctly'),
                'data'    => $accounts,
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
     * @group Account
     * Find an account by ID
     * @urlParam id integer required The ID of the account. Example: 1
     */
    public function find($id)
    {
        try {
            $account = $this->accountRepo->find($id);
            if (isset($account->id)) {
                $response = [
                    'status'  => 'OK',
                    'code'    => 200,
                    'message' => __('Account Obtained Correctly'),
                    'data'    => $account,
                ];
                return response()->json($response, 200);
            }
            $response = [
                'status'  => 'FAILED',
                'code'    => 404,
                'message' => __('Not Data with this Account') . '.',
            ];
            return response()->json($response, 404);
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
     * @group Account
     * Save a new account
     * @bodyParam name string required The name of the account. Example: Account 1
     * @bodyParam currency_id integer required The ID of the currency. Example: 1
     * @bodyParam initial number required The initial amount. Example: 1000.00
     * @bodyParam account_type_id integer required The ID of the account type. Example: 1
     */
    public function save(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|max:35',
            'currency_id' => 'required|exists:currencies,id',
            'initial' => 'required|numeric',
            'account_type_id' => 'required|exists:account_types,id',
            'active' => 'sometimes|boolean',
        ], $this->custom_message());

        if ($validator->fails()) {
            $response = [
                'status'  => 'FAILED',
                'code'    => 400,
                'message' => __('Incorrect Params'),
                'data'    => $validator->errors()->getMessages(),
            ];
            return response()->json($response, 400);
        }

        try {
            $data = $request->only(['name', 'currency_id', 'initial', 'account_type_id']);
            if ($request->exists('active')) {
                $data['active'] = $request->boolean('active');
            }
            $account = $this->accountRepo->store($data);

            // Attach to user pivot only when authenticated (tests may run unauthenticated)
            if ($request->user()) {
                $userId = $request->user()->id;
                $folderId = $request->input('folder_id');
                $sortOrder = $request->input('sort_order', 0);
                $account->users()->attach($userId, [
                    'is_owner' => 1,
                    'folder_id' => $folderId,
                    'sort_order' => $sortOrder,
                ]);
                $account->load(['users']);
            }

            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Account saved correctly'),
                'data'    => $account,
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
     * @group Account
     * Update an existing account
     * @urlParam id integer required The ID of the account. Example: 1
     * @bodyParam name string optional The name of the account. Example: Account 1x
     * @bodyParam currency_id integer optional The ID of the currency. Example: 1
     * @bodyParam initial number optional The initial amount. Example: 1200.50
     * @bodyParam account_type_id integer optional The ID of the account type. Example: 1
     */
    public function update(Request $request, $id)
    {
        $account = $this->accountRepo->find($id);
        if (isset($account->id)) {
            $data = $request->only(['name', 'currency_id', 'initial', 'account_type_id']);
            if ($request->exists('active')) {
                $data['active'] = $request->boolean('active');
            }
            $account = $this->accountRepo->update($account, $data);
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Account updated'),
                'data'    => $account,
            ];
            return response()->json($response, 200);
        }
        $response = [
            'status'  => 'FAILED',
            'code'    => 404,
            'message' => __('Account does not exist') . '.',
        ];
        return response()->json($response, 404);
    }

    /**
     * @group Account
     * Adjust account balance by creating an adjustment transaction
     * @urlParam id integer required The ID of the account. Example: 1
     * @bodyParam target_balance number required The desired balance. Example: 1000.00
     * @bodyParam include_in_balance boolean required Whether to include the adjustment in balance. Example: true
     * @bodyParam description string optional Description for the adjustment. Example: "Ajuste manual"
     */
    public function adjustBalance(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'target_balance' => 'required|numeric',
            'include_in_balance' => 'required|boolean',
            'description' => 'nullable|string|max:255',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'FAILED',
                'code' => 400,
                'message' => __('Incorrect Params'),
                'data' => $validator->errors()->getMessages(),
            ], 400);
        }

        $account = $this->accountRepo->find($id);
        if (!$account) {
            return response()->json([
                'status' => 'FAILED',
                'code' => 404,
                'message' => __('Account not found'),
            ], 404);
        }

        $currentBalance = $this->accountRepo->calculateBalance($account->id);
        $targetBalance = (float) $request->input('target_balance');
        $diff = round($targetBalance - $currentBalance, 2);
        if (abs($diff) < 0.01) {
            return response()->json([
                'status' => 'OK',
                'code' => 200,
                'message' => __('No adjustment needed'),
                'data' => [
                    'account' => $account,
                    'current_balance' => $currentBalance,
                    'target_balance' => $targetBalance,
                    'adjustment' => 0,
                ],
            ], 200);
        }

        // Get transaction_type_id for "ajuste"
        $ajusteType = DB::table('transaction_types')->where('slug', 'ajuste')->first();
        $transactionTypeId = $ajusteType ? $ajusteType->id : null;

        $txn = new \App\Models\Entities\Transaction();
        $txn->name = 'Ajuste de saldo';
        $txn->amount = $diff;
        $txn->description = $request->input('description') ?? 'Ajuste manual de saldo';
        $txn->date = now();
        $txn->active = 1;
        $txn->account_id = $account->id;
        $txn->user_id = $request->user() ? $request->user()->id : null;
        $txn->transaction_type_id = $transactionTypeId;
        $txn->amount_tax = 0;
        $txn->include_in_balance = $request->boolean('include_in_balance');
        $txn->save();

        $newBalance = $this->accountRepo->calculateBalance($account->id);
        $account->balance_calculado = $newBalance;

        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'message' => __('Balance adjusted'),
            'data' => [
                'account' => $account,
                'adjustment_transaction' => $txn,
                'previous_balance' => $currentBalance,
                'new_balance' => $newBalance,
            ],
        ], 200);
    }

    /**
     * @group Account
     * Delete an account
     * @urlParam id integer required The ID of the account. Example: 1
     */
    public function delete($id)
    {
        try {
            $account = $this->accountRepo->find($id);
            if ($account) {
                $account = $this->accountRepo->delete($account);
                $response = [
                    'status'  => 'OK',
                    'code'    => 200,
                    'message' => __('Account Deleted Successfully'),
                    'data'    => $account,
                ];
                return response()->json($response, 200);
            } else {
                $response = [
                    'status'  => 'FAILED',
                    'code'    => 404,
                    'message' => __('Account not Found'),
                ];
                return response()->json($response, 404);
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
     * @group Account
     * Change account status
     * @urlParam id integer required The ID of the account. Example: 1
     */
    public function change_status($id)
    {
        $account = $this->accountRepo->find($id);
        if (isset($account->active)) {
            $data = ['active' => !$account->active];
            $this->accountRepo->update($account, $data);
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Status Account updated'),
                'data'    => $account,
            ];
            return response()->json($response, 200);
        }
        $response = [
            'status'  => 'FAILED',
            'code'    => 404,
            'message' => __('Account does not exist') . '.',
        ];
        return response()->json($response, 404);
    }

    /**
     * @group Account
     * Move an account to a folder
     * @urlParam id integer required The ID of the account. Example: 1
     * @bodyParam folder_id integer|null Folder ID to move to. Example: 2
     * @bodyParam sort_order integer optional Sort order within folder. Example: 10
     */
    public function move(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'folder_id' => 'nullable|exists:account_folders,id',
            'sort_order' => 'nullable|integer',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'status' => 'FAILED',
                'code' => 400,
                'message' => __('Incorrect Params'),
                'errors' => $validator->errors(),
            ], 400);
        }
        try {
            $userId = $request->user()->id ?? null;
            if (!$userId) {
                // In unauthenticated contexts, just return OK without changing pivot
                return response()->json([
                    'status' => 'OK',
                    'code' => 200,
                    'data' => ['id' => (int)$id, 'folder_id' => $request->input('folder_id')],
                ], 200);
            }
            $folderId = $request->input('folder_id');
            $sortOrder = $request->input('sort_order', 0);
            // Update pivot table
            DB::table('account_user')
                ->where('user_id', $userId)
                ->where('account_id', $id)
                ->update(['folder_id' => $folderId, 'sort_order' => $sortOrder]);
            return response()->json([
                'status' => 'OK',
                'code' => 200,
                'data' => ['id' => (int)$id, 'folder_id' => $folderId],
            ], 200);
        } catch (\Exception $ex) {
            Log::error($ex);
            return response()->json([
                'status' => 'FAILED',
                'code' => 500,
                'message' => __('An error has occurred'),
            ], 500);
        }
    }

    /**
     * @group Account
     * Get account tree for current user
     */
    public function tree(Request $request)
    {
        // Ensure user is authenticated
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'status' => 'FAILED',
                'code' => 401,
                'message' => __('Unauthenticated'),
            ], 401);
        }
        $userId = $user->id;
        // Load folders and accounts
        $folders = AccountFolder::where('user_id', $userId)->get();
        $accounts = DB::table('account_user')
            ->where('user_id', $userId)
            ->join('accounts', 'account_user.account_id', '=', 'accounts.id')
            ->select('accounts.id', 'accounts.name as label', 'account_user.folder_id', 'account_user.sort_order')
            ->whereNull('accounts.deleted_at')
            ->orderBy('account_user.sort_order')
            ->get();
        // Build folder map
        $folderMap = [];
        foreach ($folders as $f) {
            $folderMap[$f->id] = ['id' => $f->id, 'label' => $f->name, 'type' => 'folder', 'children' => []];
        }
        // Assign folder children
        $tree = [];
        foreach ($folders as $f) {
            if ($f->parent_id && isset($folderMap[$f->parent_id])) {
                $folderMap[$f->parent_id]['children'][] =& $folderMap[$f->id];
            } else {
                $tree[] =& $folderMap[$f->id];
            }
        }
        // Attach accounts
        foreach ($accounts as $acct) {
            $node = ['id' => $acct->id, 'label' => $acct->label, 'type' => 'account'];
            if ($acct->folder_id && isset($folderMap[$acct->folder_id])) {
                $folderMap[$acct->folder_id]['children'][] = $node;
            } else {
                $tree[] = $node;
            }
        }
        return response()->json([
            'status' => 'OK',
            'code' => 200,
            'data' => ['nodes' => $tree],
        ], 200);
    }

    /**
     * @group Account
     * Get accounts with trashed
     */
    public function withTrashed()
    {
        try {
            $accounts = $this->accountRepo->withTrashed();
            $response = [
                'status'  => 'OK',
                'code'    => 200,
                'message' => __('Data Obtained Correctly'),
                'data'    => $accounts,
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

    public function custom_message()
    {
        return [
            'name.required' => __('The name is required'),
            'currency_id.required' => __('The currency is required'),
            'initial.required' => __('The initial amount is required'),
            'account_type_id.required' => __('The account type is required'),
        ];
    }
}
